<?php

namespace App\Providers;

use App\Events\AdviceRequestCreate;
use App\Events\ContactInfoRequest;
use App\Events\InvestmentApplicantCreate;
use App\Events\InvestmentRequestCreate;
use App\Events\PropertyImpression;
use App\Events\PropertySearch;
use App\Events\PropertyVisit;
use App\Events\QuestionVisit;
use App\Events\RealtorOffer;
use App\Listeners\IncreaseQuestionVisitCount;
use App\Listeners\LogCommandExecuted;
use App\Listeners\LogQueryExecuted;
use App\Listeners\SendAdviceRequestNotification;
use App\Listeners\SendInvestmentApplicantNotification;
use App\Listeners\SendInvestmentApplicantNotificationToApplicator;
use App\Listeners\SendInvestmentRequestNotification;
use App\Listeners\StoreContactInfoRequestLog;
use App\Listeners\StorePropertiesImpression;
use App\Listeners\StorePropertySearchStat;
use App\Listeners\StorePropertyVisitLog;
use App\Listeners\StoreRealtorSuggestLog;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Redis\Events\CommandExecuted;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // CommandExecuted::class => [
        //     LogCommandExecuted::class
        // ],
        // QueryExecuted::class => [
        //     LogQueryExecuted::class
        // ],
    ];
}
