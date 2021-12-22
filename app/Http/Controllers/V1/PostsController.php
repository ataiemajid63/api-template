<?php

namespace App\Http\Controllers\V1;

use App\Contracts\Settings;
use App\Entities\Comment;
use App\Enums\HttpStatusCode;
use App\Enums\PostType;
use App\Enums\ShortLinkItemType;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CommentCompactResourceCollection;
use App\Http\Resources\V1\CommentLikeAndDislikeResource;
use App\Http\Resources\V1\CommentResource;
use App\Http\Resources\V1\PostCompactResourceCollection;
use App\Http\Resources\V1\PostMetasCompactResourceCollection;
use App\Http\Resources\V1\PostResource;
use App\Http\Response;
use App\Repositories\AgencyRepository;
use App\Repositories\CommentRepository;
use App\Repositories\PostMetaRepository;
use App\Repositories\PostRepository;
use App\Repositories\ShortLinkRepository;
use App\Repositories\TagRelationRepository;
use App\Repositories\TagRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Pasoonate\Pasoonate;

class PostsController extends Controller
{
    private $postRepository;
    private $postMetaRepository;
    private $userRepository;
    private $commentRepository;
    private $agencyRepository;
    private $tagRepository;
    private $tagRelationRepository;
    private $shortLinkRepository;

    public function __construct(PostRepository $postRepository, PostMetaRepository $postMetaRepository, UserRepository $userRepository, CommentRepository $commentRepository, AgencyRepository $agencyRepository, TagRepository $tagRepository, TagRelationRepository $tagRelationRepository, ShortLinkRepository $shortLinkRepository)
    {
        parent::__construct();

        $this->postRepository = $postRepository;
        $this->postMetaRepository = $postMetaRepository;
        $this->userRepository = $userRepository;
        $this->commentRepository = $commentRepository;
        $this->agencyRepository = $agencyRepository;
        $this->tagRepository = $tagRepository;
        $this->tagRelationRepository = $tagRelationRepository;
        $this->shortLinkRepository = $shortLinkRepository;
    }

    public function getPostsByCityId(Settings $settings, $cityId = null)
    {
        $postIds = json_decode($settings->value('blogs_ids_for_' . $cityId));

        $posts = collect();

        if (!is_null($postIds)) {
            $posts = $this->postRepository->getAllByIds($postIds);
        }

        $data = [
            'posts' => (new PostCompactResourceCollection($posts))->toArray()
        ];

        return new Response($data);
    }

    public function getAllPosts(Settings $settings, Request $request)
    {
        $total = 0;
        $type = $request->get('type', PostType::BLOG);
        $page = $request->get('page', 1);
        $count = $request->get('count', 20);

        $mainPostId = $settings->value('manipedia_main_post_id');
        $topPostsIds = $settings->value('manipedia_top_post_ids');
        if (!empty($topPostsIds)) {
            $topPostsIds = json_decode($topPostsIds, true);
        } else {
            $topPostsIds = [];
        }
        $allPostsIds = array_merge($topPostsIds, [$mainPostId]);

        $posts = $this->postRepository->getAllPosts($type, $page, $count, $total, $allPostsIds);

        $data = [
            'posts' => (new PostCompactResourceCollection($posts))->toArray(),
            'total' => $total
        ];

        return new Response($data);
    }

    public function getMainPost(Settings $settings)
    {
        $mainPostId = $settings->value('manipedia_main_post_id');

        $post = null;

        if (!empty($mainPostId)) {
            $post = $this->postRepository->getOneById($mainPostId);
        }

        if (empty($post) or !$post->getPublished()) {
            $data = [
                'errors' => [
                    'post_id' => ['validation:published']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if (!empty($post) and !empty($post->getUserId())) {
            $author = $this->userRepository->getOneById($post->getUserId());

            $post->setAuthor($author);
        }

        $data = [
            'post' => (new PostResource($post))->toArray()
        ];
        return new Response($data);
    }

    public function getTopPosts(Settings $settings)
    {
        $topPostsIds = $settings->value('manipedia_top_post_ids');
        if (!empty($topPostsIds)) {
            $topPostsIds = json_decode($topPostsIds, true);
        } else {
            $topPostsIds = [];
        }

        $posts = collect();

        if (!empty($topPostsIds)) {
            $posts = $this->postRepository->getAllByIds($topPostsIds);
        }

        $data = [
            'posts' => (new PostCompactResourceCollection($posts))->toArray()
        ];
        return new Response($data);
    }

    public function getPost($id)
    {
        $post = $this->postRepository->getOneById($id);

        if (empty($post)) {
            $data = [
                'errors' => [
                    'post_id' => ['validation:exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $sidebarTitles = $this->postMetaRepository->getAllByPostIdAndName($post->getId(), 'sidebar_title%');

        if (!empty($post->getUserId())) {
            $author = $this->userRepository->getOneById($post->getUserId());

            $post->setAuthor($author);
        }

        $shortLink = $this->shortLinkRepository->getOneByItemIdAndItemType($post->getId(), ShortLinkItemType::POST);

        if (!empty($shortLink)) {
            $post->setShortLink($shortLink->getLink());
        }

        if (empty($post->getMetaDescription()) and !empty($post->getTagsId())) {
            $tagIds = explode(',', $post->getTagsId());

            if (count($tagIds)) {
                $tags = $this->tagRepository->getAllByIds($tagIds);

                if ($tags->isNotEmpty()) {
                    $mainTag = $tags->whereNull('parent_id')->first();
                    $metaDescription = $post->getTitle() . ' در رابطه با ';

                    $tagsDescription = explode(',', $post->getTagsTitle());

                    $tagIdToRemove = array_search($mainTag->getName(), $tagsDescription);

                    if (is_numeric($tagIdToRemove)) {
                        unset($tagsDescription[$tagIdToRemove]);
                    }

                    if (!empty($tagsDescription)) {
                        $metaDescription .= implode('، ', $tagsDescription);
                    }

                    $metaDescription .= ' از ' . $mainTag->getName() . ' در وبلاگ ۲نبش.';

                    $post->setMetaDescription($metaDescription);
                }
            }
        }

        $data = [
            'post' => (new PostResource($post))->toArray(),
            'sidebarTitles' => (new PostMetasCompactResourceCollection($sidebarTitles))->toArray()
        ];

        return new Response($data);
    }

    private function getCommentsUserAndReplies(Collection $comments, $replies = true)
    {
        if ($comments->isNotEmpty()) {
            $userIds = $comments->pluck('userId')->toArray();
            $userIds = array_unique($userIds);

            $allUsers = $this->userRepository->getAllByIds($userIds);
            $allUsers = $allUsers->keyBy('id');

            foreach ($comments as $comment) {

                if ($replies) {
                    $replies = $this->commentRepository->getAllRepliesByCommentId($comment->getId());

                    $replies = $this->getCommentsUserAndReplies($replies, false);

                    $comment->setReplies($replies);
                }

                if (!empty($userIds) and count($userIds) and !empty($comment->getUserId()) and in_array($comment->getUserId(), $userIds)) {
                    $comment->setUser($allUsers[$comment->getUserId()]);

                    $agencyId = $comment->getUser()->getAgencyId();
                    $agency = !empty($agencyId) ? $this->agencyRepository->getOneById($agencyId) : null;
                    $comment->setUserAgency($agency);
                }
            }
        }

        return $comments;
    }

    public function getComments(Request $request, $id)
    {
        /*$page = $request->get('page', 1);
        $count = $request->get('count', 20);*/
        $total = 0;

        $comments = $this->commentRepository->getAllByItemId($id, $total);

        $comments = $this->getCommentsUserAndReplies($comments);

        $data = [
            'comments' => (new CommentCompactResourceCollection($comments))->toArray(),
            'total' => $total
        ];

        return new Response($data);
    }

    public function storePostComment(Request $request, $postId)
    {
        $validator = $this->makeValidator($request, [
            'body' => 'required',
            'author_name' => 'nullable'
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $post = $this->postRepository->getOneById($postId);

        if (empty($post)) {
            $data = [
                'errors' => [
                    'post_id' => 'validation.exists'
                ]
            ];

            return new  Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        $jalaliTime = Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm:ss');
        $newComment = new Comment();

        $newComment->setId(null);
        $newComment->setParentId(null);
        $newComment->setUserId(!empty($user) ? $user->getId() : null);
        $newComment->setAuthorName($user ? $user->fullname() : $request->get('author_name', ''));
        $newComment->setItemId($post->getId());
        $newComment->setBody($request->get('body'));
        $newComment->setEmail($user ? $user->getEmail() : null);
        $newComment->setRate(0);
        $newComment->setPublished(0);
        $newComment->setLikeCount(0);
        $newComment->setDislikeCount(0);
        $newComment->setCreatedAt(time());
        $newComment->setCreatedAtJalali($jalaliTime);
        $newComment->setUpdatedAt(time());

        $comment = $this->commentRepository->insert($newComment);
        $comment = $this->getCommentsUserAndReplies(collect([$comment]), false)[0];

        $post->setCommentCount($post->getCommentCount() + 1);

        $this->postRepository->update($post);

        $data = [
            'comment' => (new CommentResource($comment))->toArray()
        ];

        return new Response($data);
    }

    public function storeCommentReply(Request $request, $postId, $commentId)
    {
        $validator = $this->makeValidator($request, [
            'body' => 'required',
            'author_name' => 'nullable'
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $comment = $this->commentRepository->getOneById($commentId);

        if (empty($comment) or $comment->getItemId() != $postId) {
            $data = [
                'errors' => [
                    'comment_id' => 'validation.exists'
                ]
            ];

            return new  Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if (!$comment->getPublished()) {
            $data = [
                'errors' => [
                    'comment_id' => 'validation.published'
                ]
            ];

            return new  Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        $jalaliTime = Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm:ss');
        $newComment = new Comment();

        $newComment->setId(null);
        $newComment->setParentId($comment->getId());
        $newComment->setUserId(!empty($user) ? $user->getId() : null);
        $newComment->setAuthorName($user ? $user->fullname() : $request->get('author_name', ''));
        $newComment->setItemId($postId);
        $newComment->setBody($request->get('body'));
        $newComment->setEmail($user ? $user->getEmail() : null);
        $newComment->setRate(0);
        $newComment->setPublished(0);
        $newComment->setLikeCount(0);
        $newComment->setDislikeCount(0);
        $newComment->setCreatedAt(time());
        $newComment->setCreatedAtJalali($jalaliTime);
        $newComment->setUpdatedAt(time());

        $reply = $this->commentRepository->insert($newComment);

        $reply = $this->getCommentsUserAndReplies(collect([$reply]), false)[0];

        $data = [
            'comment' => (new CommentResource($reply))->toArray()
        ];

        return new Response($data);
    }

    public function getSuggestedPosts(Request $request, $postId)
    {
        $post = $this->postRepository->getOneById($postId);

        if (empty($post)) {
            $data = [
                'errors' => [
                    'post_id' => 'validation.exists'
                ]
            ];

            return new  Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $posts = new Collection();
        $suggestedTake = $request->get('count', 6);
        $tag = $this->tagRepository->getOneById($post->getTagsId());

        if (!empty($post->getSuggestions())) {
            $suggestionsPostsIds = explode(',', $post->getSuggestions());

            if (is_array($suggestionsPostsIds) and count($suggestionsPostsIds)) {
                $suggestionsPosts = $this->postRepository->getLimitByIdsAndOrderByRand($suggestionsPostsIds, $suggestedTake);

                if ($suggestionsPosts->isNotEmpty()) {
                    $posts = $posts->merge($suggestionsPosts);
                }
            }
        }

        if ($posts->isNotEmpty()) {
            $suggestedTake = $suggestedTake - $posts->count();
        }

        if ($suggestedTake < 0) {
            $suggestedTake = 0;
        }

        if (!empty($tag) and $suggestedTake > 0) {
            $postsIds = [];

            if ($posts->isNotEmpty()) {
                $postsIds = $posts->pluck('id')->toArray();
            }

            $tagsPosts = $this->tagRelationRepository->getAllRelationPostsByTagId($tag->getId(), $postsIds);

            if ($tagsPosts->isNotEmpty()) {
                $tagsPostsIds = $tagsPosts->pluck('id')->toArray();

                if (is_array($tagsPostsIds) and count($tagsPostsIds)) {
                    $suggestionsPostsTagRelation = $this->postRepository->getLimitByIdsAndOrderByRand($tagsPostsIds, $suggestedTake);

                    if ($suggestionsPostsTagRelation->isNotEmpty()) {
                        $posts = $posts->merge($suggestionsPostsTagRelation);
                    }
                }
            }
        }

        $data = [
            'posts' => (new PostCompactResourceCollection($posts))->toArray()
        ];

        return new Response($data);
    }

    public function likePostComment(Request $request, $postId, $commentId)
    {
        $comment = $this->commentRepository->getOneById($commentId);

        if (empty($comment->getId()) or $comment->getItemId() != $postId) {
            $data = [
                'errors' => [
                    'comment_id' => 'validation.exists'
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if (!$comment->getPublished()) {
            $data = [
                'errors' => [
                    'comment_id' => 'validation.published'
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $session = $request->header('Session');

        $this->commentRepository->like($comment->getId(), $session);

        $comment = $this->commentRepository->getOneById($commentId);

        return new Response((new CommentLikeAndDislikeResource($comment))->toArray());
    }

    public function dislikePostComment(Request $request, $postId, $commentId)
    {
        $comment = $this->commentRepository->getOneById($commentId);

        if (empty($comment->getId()) or $comment->getItemId() != $postId) {
            $data = [
                'errors' => [
                    'comment_id' => 'validation.exists'
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if (!$comment->getPublished()) {
            $data = [
                'errors' => [
                    'comment_id' => 'validation.published'
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $session = $request->header('Session');

        $this->commentRepository->dislike($comment->getId(), $session);

        $comment = $this->commentRepository->getOneById($commentId);

        return new Response((new CommentLikeAndDislikeResource($comment))->toArray());
    }
}
