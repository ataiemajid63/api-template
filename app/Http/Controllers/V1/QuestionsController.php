<?php

namespace App\Http\Controllers\V1;

use App\Entities\Answer;
use App\Entities\AnswerComment;
use App\Entities\Question;
use App\Entities\User;
use App\Enums\HttpStatusCode;
use App\Enums\PurgeTurbuCacheType;
use App\Enums\QuestionNotifyVia;
use App\Enums\TagRelations;
use App\Events\QuestionVisit;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AnswerCommentResourceCollection;
use App\Http\Resources\V1\AnswerResourceCollection;
use App\Http\Resources\V1\PostCompactResourceCollection;
use App\Http\Resources\V1\QuestionResource;
use App\Http\Resources\V1\QuestionResourceCollection;
use App\Http\Response;
use App\Jobs\PurgeTurbuCache;
use App\Repositories\AgencyRepository;
use App\Repositories\AnswerCommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\AnswerRepository;
use App\Repositories\TagRelationRepository;
use App\Repositories\TagRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Pasoonate\Pasoonate;

class QuestionsController extends Controller
{
    private $questionRepository;
    private $answerRepository;
    private $answerCommentRepository;
    private $userRepository;
    private $agencyRepository;
    private $tagRepository;
    private $tagRelationRepository;
    private $postRepository;

    public function __construct(QuestionRepository $questionRepository, AnswerRepository $answerRepository, AnswerCommentRepository $answerCommentRepository, UserRepository $userRepository, AgencyRepository $agencyRepository, TagRepository $tagRepository, TagRelationRepository $tagRelationRepository, PostRepository $postRepository)
    {
        parent::__construct();

        $this->questionRepository = $questionRepository;
        $this->answerRepository = $answerRepository;
        $this->answerCommentRepository = $answerCommentRepository;
        $this->userRepository = $userRepository;
        $this->agencyRepository = $agencyRepository;
        $this->tagRepository = $tagRepository;
        $this->tagRelationRepository = $tagRelationRepository;
        $this->postRepository = $postRepository;
    }

    public function search(Request $request)
    {
        $query = $request->get('query', '');
        $page = $request->get('page', 1);
        $count = $request->get('count', 20);
        $total = 0;

        $questions = $this->questionRepository->getAllBySearch($query, $page, $count, $total);

        $data = [
            'questions' => (new QuestionResourceCollection($questions))->toArray(),
            'total' => $total
        ];

        return new Response($data);
    }

    public function getQuestion($id)
    {
        $question = $this->questionRepository->getOneById($id);

        if (is_null($question)) {
            $data = [
                'errors' => [
                    'question_id' => ['validation:exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if (!$question->getApproved()) {
            $data = [
                'errors' => [
                    'question_id' => ['validation:approved']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $answers = $this->answerRepository->getByQuestionId($id);

        if ($answers->isNotEmpty()) {
            $answerIds = $answers->pluck('id')->all();
            $answersComments = $this->answerCommentRepository->getAllByAnswerIds($answerIds);
            $answersComments = $answersComments->groupBy('answerId');

            $answererIds = $answers->pluck('userId')->all();
            $answerers = $this->userRepository->getAllByIds($answererIds);
            $answerers = $answerers->keyBy('id');

            /**
             * @var Answer $answer
             */
            foreach ($answers as $answer) {
                $answer->setComments($answersComments[$answer->getId()] ?? collect());
                $answer->setUser($answerers[$answer->getUserId()]);

                $agencyId = $answer->getUser()->getAgencyId();
                $agency = !empty($agencyId) ? $this->agencyRepository->getOneById($agencyId) : null;
                $answer->setUserAgency($agency);
            }
        }

        event(new QuestionVisit($question));

        $data = [
            'question' => (new QuestionResource($question))->toArray(),
            'answers' => (new AnswerResourceCollection($answers))->toArray()
        ];

        return new Response($data);
    }

    public function storeQuestion(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'author_name' => 'nullable',
            'body' => 'required',
            'district_id' => 'nullable|int',
            'email' => 'required_if:notify_by_email,1|nullable|email',
            'notify_by_email' => 'required|int'
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        /**
         * @var User $user
         */
        $user = $request->user();

        $question = new Question();

        $question->setId(null);
        $question->setSlug(md5(uniqid()));
        $question->setUserId(!empty($user) ? $user->getId() : '');
        $question->setAuthorName(!empty($user) ? $user->fullName() : $request->get('author_name'));
        $question->setAnswerId(null);
        $question->setTitle('');
        $question->setBody($request->get('body'));
        $question->setAdminDescription(null);
        $question->setTagsId('');
        $question->setTagsTitle('');
        $question->setDistrictId($request->get('district_id'));
        $question->setSelectedAnswersIds(null);
        $question->setVisitCount(0);
        $question->setAnswerCount(0);
        $question->setRate('');
        $question->setNotifyVia($request->get('notify_by_email') ? QuestionNotifyVia::EMAIL : null);
        $question->setNotifyRealtors(null);
        $question->setSmsCount(null);
        $question->setSmsCancled(null);
        $question->setApproved(0);
        $question->setApprovedAt(null);
        $question->setPublishedAt(null);
        $question->setCreatedAt(time());
        $question->setCreatedAtJalali(Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm'));
        $question->setUpdatedAt(time());

        $this->questionRepository->insert($question);

        return new Response();
    }

    public function storeAnswer(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'question_id' => 'required|int',
            'body' => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $question = $this->questionRepository->getOneById($request->get('question_id'));

        if (is_null($question)) {
            $data = [
                'errors' => [
                    'question_id' => 'validation.exists'
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        /**
         * @var User $user
         */
        $user = $request->user();

        $answer = new Answer();

        $answer->setId(null);
        $answer->setUserId($user->getId());
        $answer->setAuthorName($user->fullName());
        $answer->setQuestionId($question->getId());
        $answer->setBody($request->get('body'));
        $answer->setRate(null);
        $answer->setLikeCount(0);
        $answer->setApproved(1);
        $answer->setApprovedAt(time());
        $answer->setCreatedAt(time());
        $answer->setCreatedAtJalali(Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm'));

        $this->answerRepository->insert($answer);

        $question->setAnswerCount($question->getAnswerCount() + 1);
        $question->setUpdatedAt(time());

        $this->questionRepository->update($question);

        $this->PurgeQuestionTurbuCache($question->getId(), 'updated');

        $this->answerRepository->purgeByQuestionId($question->getId());

        $answer->setComments(collect());
        $answer->setUser($user);
        $agencyId = $answer->getUser()->getAgencyId();
        $agency = !empty($agencyId) ? $this->agencyRepository->getOneById($agencyId) : null;
        $answer->setUserAgency($agency);

        $answerResource = (new AnswerResourceCollection(collect([$answer])))->toArray();

        $data = [
            'answer' => !empty($answerResource) ? $answerResource[0] : null
        ];

        return new Response($data);
    }

    public function storeComment(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'answer_id' => 'required|int',
            'body' => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $answer = $this->answerRepository->getOneById($request->get('answer_id'));

        if (is_null($answer) || !$answer->getApproved()) {
            $data = [
                'errors' => [
                    'answer_id' => ['validation:exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        /**
         * @var User $user
         */
        $user = $request->user();

        $answerComment = new AnswerComment();

        $answerComment->setId(null);
        $answerComment->setUserId($user->getId());
        $answerComment->setAuthorName($user->fullName());
        $answerComment->setAnswerId($request->get('answer_id'));
        $answerComment->setBody($request->get('body'));
        $answerComment->setApproved(1);
        $answerComment->setApprovedAt(time());
        $answerComment->setCreatedAt(time());
        $answerComment->setCreatedAtJalali(Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm'));

        $this->answerCommentRepository->insert($answerComment);

        $this->PurgeQuestionTurbuCache($answer->getQuestionId(), 'updated');

        $this->answerCommentRepository->purgeByAnswerId($answer->getId());

        $answers = $this->answerRepository->getByQuestionId($answer->getQuestionId());
        if ($answers->isNotEmpty()) {
            $answerIds = $answers->pluck('id')->all();
            $this->answerCommentRepository->purgeByAnswerIds($answerIds);
        }

        $comment = $this->answerCommentRepository->getCommentsUsers(collect([$answerComment]));
        $comment = (new AnswerCommentResourceCollection($comment))->toArray();

        $data = [
            'comment' => !empty($comment) ? $comment[0] : null
        ];

        return new Response($data);
    }

    public function toggleAnswerLike(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'answer_id' => 'required|int',
            'like' => 'boolean'
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $answer = $this->answerRepository->getOneById($request->get('answer_id'));
        $like = boolval($request->get('like', 0));

        if (is_null($answer) || !$answer->getApproved()) {
            $data = [
                'errors' => [
                    'answer_id' => ['validation:exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if ($like) {
            $this->answerRepository->like($answer->getId());
        } else {
            $this->answerRepository->disLike($answer->getId());
        }

        $this->PurgeQuestionTurbuCache($answer->getQuestionId(), 'updated');

        return new Response($answer->getId);
    }

    public function getRelatedPosts($id)
    {
        $question = $this->questionRepository->getOneById($id);

        if (is_null($question)) {
            $data = [
                'errors' => [
                    'question_id' => ['validation:exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if (!$question->getApproved()) {
            $data = [
                'errors' => [
                    'question_id' => ['validation:approved']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $posts = collect();

        if (!empty($question->getTagsId())) {
            $tagRelations = $this->tagRelationRepository->getAllByTagsIds([$question->getTagsId()], TagRelations::POST);

            if ($tagRelations->isNotEmpty()) {
                $postsIds = $tagRelations->pluck('postId')->toArray();

                $posts = $this->postRepository->getLimitByIdsAndOrderByRand(array_unique($postsIds), 5);
            }
        }

        $data = [
            'posts' => (new PostCompactResourceCollection($posts))->toArray(),
        ];

        return new Response($data);
    }

    private function PurgeQuestionTurbuCache($questionId, $type)
    {
        $purgeCacheParams = [
            'id' => $questionId,
            'type' => $type
        ];

        $this->dispatch(new PurgeTurbuCache(PurgeTurbuCacheType::QUESTIONS, $purgeCacheParams));
    }
}
