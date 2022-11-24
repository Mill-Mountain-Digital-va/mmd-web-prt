<?php

namespace App\Http\Controllers\v2;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\Course;

use Illuminate\Http\Request;


use App\Repositories\Frontend\Auth\UserRepository;

/**
 * Class MarketController
 * @package App\Http\Controllers\API
 */

class WorkoutsController extends Controller
{
    /**
     * @var UserRepository
     */
    private $userRepository;


    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Display a listing of WORKOUTS.
     * GET|HEAD /markets
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $types = ['popular', 'trending', 'featured'];
        $type = ($request->type) ? $request->type : null;
        if ($type != null) {
            if (in_array($type, $types)) {
                $courses = Course::where('published', '=', 1)
                    ->where($type, '=', 1)
                    ->paginate(10);
            } else {
                return response()->json(['status' => 'failure', 'message' => 'Invalid Request']);
            }
        } else {
            $courses = Course::where('published', '=', 1)
                ->paginate(10);
        }

        return response()->json(['status' => 'success', 'type' => $type, 'result' => $courses]);
    }

    /**
     * Display the specified Workout.
     * GET|HEAD /workouts/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        if(isset($id) && !empty($id)){

            // $workout = Course::with('category', 'lessons')->where('id', $id)->first();
            $course = Course::withoutGlobalScope('filter')->with('category')->where('id', '=', $id)->with('publishedLessons')->first(); // 'teachers', 


            // $continue_course = null;
            // $course_timeline = null;
            
            if ($course == null) {
                return response()->json(['status' => 'failure', 'result' => null]);
            }

            // $purchased_course = \Auth::check() && $course->students()->where('user_id', \Auth::id())->count() > 0;
            // $course_rating = 0;
            // $total_ratings = 0;
            // $completed_lessons = [];
            // $is_reviewed = false;
            // if (auth()->check() && $course->reviews()->where('user_id', '=', auth()->user()->id)->first()) {
            //     $is_reviewed = true;
            // }
            // if ($course->reviews->count() > 0) {
            //     $course_rating = $course->reviews->avg('rating');
            //     $total_ratings = $course->reviews()->where('rating', '!=', "")->get()->count();
            // }
            // $lessons = $course->courseTimeline()->orderby('sequence', 'asc')->get();


            // if (\Auth::check()) {
            //     $completed_lessons = \Auth::user()->chapters()->where('course_id', $course->id)->get()->pluck('model_id')->toArray();
            //     $continue_course = $course->courseTimeline()->orderby('sequence', 'asc')->whereNotIn('model_id', $completed_lessons)->first();
            //     if ($continue_course == null) {
            //         $continue_course = $course->courseTimeline()->orderby('sequence', 'asc')->first();
            //     }
            // }

            // if ($course->courseTimeline) {
            //     $timeline = $course->courseTimeline()->orderBy('sequence')->get();
            //     foreach ($timeline as $item) {
            //         $completed = false;
                    
            //         if (in_array($item->model_id, $completed_lessons)) {
            //             $completed = true;
            //         }
            //         $type = $item->model->live_lesson?'live_lesson':'lesson';
            //         $slots = [];
            //         if($item->model->live_lesson){
            //             if($item->model->liveLessonSlots->count()){
            //                 foreach ($item->model->liveLessonSlots as $slot){
            //                     $slots[] = $slot;
            //                 }
            //             }
            //         }
            //         $description = "";
            //         if ($item->model_type == 'App\Models\Test') {
            //             $type = 'test';
            //             $description = $item->model->description;
            //         } else {
            //             $description = $item->model->short_text;
            //         }
            //         $course_timeline[] = [
            //             'title' => $item->model->title,
            //             'type' => $type,
            //             'id' => $item->model_id,
            //             'description' => $description,
            //             'completed' => $completed,
            //             'slots' => $slots,
            //         ];
            //     }
            // }

            // $mediaVideo = (!$course->mediaVideo) ? null : $course->mediaVideo->toArray();
            // if ($mediaVideo && $mediaVideo['type'] == 'embed') {
            //     preg_match('/src="([^"]+)"/', $mediaVideo['url'], $match);
            //     $url = $match[1];
            //     $mediaVideo['file_name'] = $url;
            // }

            // $result = [
            //     'course' => $course,
            //     'course_video' => $mediaVideo,
            //     'purchased_course' => $purchased_course,
            //     'course_rating' => $course_rating,
            //     'total_ratings' => $total_ratings,
            //     'is_reviewed' => $is_reviewed,
            //     'lessons' => $lessons,
            //     'course_timeline' => $course_timeline,
            //     'completed_lessons' => $completed_lessons,
            //     'continue_course' => $continue_course,
            //     // 'is_certified' => $course->isUserCertified(),
            //     // 'course_process' => $course->progress()
            // ];
            
            // LOAD VIDEO LINK FOR EACH EXERCISE
            foreach ($course->publishedLessons as $exercise) {
                $mediaVideo = (!$exercise->mediaVideo) ? null : $exercise->mediaVideo->toArray();
                if ($mediaVideo && $mediaVideo['type'] == 'embed') {
                    preg_match('/src="([^"]+)"/', $mediaVideo['url'], $match);
                    $url = $match[1];
                    $mediaVideo['file_name'] = $url;
                }
                
                $exercise->video = $mediaVideo['url'];
            }


            return response()->json(['status' => 'success', 'result' => ['data' => $course]]);

        }else{
            // if dont have param id
            return response()->json(['status' => 'error'], 404);
        }
    }

    
    /**
     * Get WORKOUTS BY SELECTED CATEGORY ID
     *
     * @return [json] workout object
     */
    public function getCategoryWorkouts(Request $request)
    {
        if($request->has('id')){
            $input = $request->all();

            $categoryWorkouts = Course::where('category_id', $input['id'])->get();

            return response()->json(['status' => 'success', 'result' => ["data" => $categoryWorkouts]]);

        }else{
            return response()->json(['status' => 'error'], 404);
        }
       
    }

    /**
     * Get  workouts
     *
     * @return [json] workout object
     */
    public function getWorkoutsSearch(Request $request)
    {
        $types = ['popular', 'trending', 'featured'];
        $type = ($request->type) ? $request->type : null;
        
        if ($type != null) {
            if (in_array($type, $types)) {
                $courses = Course::where('title', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->search . '%')
                    ->where('published', '=', 1)
                    ->where($type, '=', 1)
                    ->paginate(10);
            } else {
                return response()->json(['status' => 'failure', 'message' => 'Invalid Request']);
            }
        } else {
            $courses = Course::where('title', 'LIKE', '%' . $request->search . '%')
                ->orWhere('description', 'LIKE', '%' . $request->search . '%')
                ->where('published', '=', 1)
                ->paginate(10);
        }

        return response()->json(['status' => 'success', 'type' => $type, 'result' => $courses]);
    }

   

    
}
