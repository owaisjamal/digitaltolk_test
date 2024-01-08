<?php
namespace DTApi\Helpers;

use Carbon\Carbon;
use DTApi\Models\Job;
use DTApi\Models\User;
use DTApi\Models\Language;
use DTApi\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeHelper
{
    public static function fetchLanguageFromJobId($id)
    {
        $language = Language::findOrFail($id);
        return $language1 = $language->language;
    }

    public static function getUsermeta($user_id, $key = false)
    {
        return $user = UserMeta::where('user_id', $user_id)->first()->$key;
        if (!$key)
            return $user->usermeta()->get()->all();
        else {
            $meta = $user->usermeta()->where('key', '=', $key)->get()->first();
            if ($meta)
                return $meta->value;
            else return '';
        }
    }

    public static function convertJobIdsInObjs($jobs_ids)
    {

        $jobs = array();
        foreach ($jobs_ids as $job_obj) {
            $jobs[] = Job::findOrFail($job_obj->id);
        }
        return $jobs;
    }

    public static function willExpireAt($due_time, $created_at)
    {
        $due_time = Carbon::parse($due_time);
        $created_at = Carbon::parse($created_at);

        $difference = $due_time->diffInHours($created_at);


        if($difference <= 90)
            $time = $due_time;
        elseif ($difference <= 24) {
            $time = $created_at->addMinutes(90);
        } elseif ($difference > 24 && $difference <= 72) {
            $time = $created_at->addHours(16);
        } else {
            $time = $due_time->subHours(48);
        }

        return $time->format('Y-m-d H:i:s');

    }

    public function testWillExpireAt()
    {
        // Create a test job
        $dueTime = Carbon::now()->addHours(10);
        $createdAt = Carbon::now();
        $job = factory(Job::class)->create([
            'due' => $dueTime,
            'created_at' => $createdAt,
        ]);

        // Mock the UserMeta model (replace the real database interaction)
        $userMetaMock = $this->getMockBuilder(UserMeta::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userMetaMock->method('where')->willReturnSelf();
        $userMetaMock->method('first')->willReturn((object)['user_id' => $job->user_id]);

        // Set the mocked UserMeta in the TeHelper class
        TeHelper::$userMetaMock = $userMetaMock;

        // Call the willExpireAt method
        $result = TeHelper::willExpireAt($job->due, $job->created_at);

        // Assert the result
        $this->assertEquals($result, $job->due);

        // Reset the mocked UserMeta in the TeHelper class (important for other tests)
        TeHelper::$userMetaMock = null;
    }

}

