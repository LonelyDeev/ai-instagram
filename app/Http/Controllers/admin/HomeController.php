<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use OpenAI;
use App\Models\Tools;
use App\Models\Content;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Blogs;
use App\Models\PricingPlan;
use App\Models\Faq;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\helper\helper;
use Stichoza\GoogleTranslate\GoogleTranslate;
use PDF;

class HomeController extends Controller
{

    public function index(Request $request)
    {

        if (Auth::user()->type == 1) {

            if (empty($request->revenue_year)) {
                $request->revenue_year = date('Y');
            }
            if (empty($request->booking_year)) {
                $request->booking_year = date('Y');
            }

            $getrevenue_data = Transaction::select(DB::raw("SUM(amount) as count"), DB::raw("MONTHNAME(purchase_date) as month"))->groupBy(DB::raw("MONTHNAME(purchase_date)"))->where(DB::raw("YEAR(purchase_date)"), $request->revenue_year)->orderby('purchase_date')->get();
            $getpiechart_data = User::select(DB::raw("COUNT(id) as count"), DB::raw("MONTHNAME(created_at) as month"))->groupBy(DB::raw("MONTHNAME(created_at)"))->where('type', 2)->where(DB::raw("YEAR(created_at)"), $request->booking_year)->orderby('created_at')->get();
            $getyears = Transaction::select(DB::raw("YEAR(purchase_date) as year"))->groupBy(DB::raw("YEAR(purchase_date)"))->orderby('purchase_date')->get();

            $getuserYears = User::select(DB::raw("YEAR(created_at) as year"))->groupBy(DB::raw("YEAR(created_at)"))->whereNotIn('id', [1])->orderby('created_at')->get();
            $userchart_year = explode(',', $getuserYears->implode('year', ','));
            $totalrevenue = Transaction::select(DB::raw("SUM(amount) as total"))->where('status', 2)->first();

            if (env('Environment') == 'sendbox') {
                $revenue_lables = ['January', 'February', 'March', 'April', 'May', 'June', 'July ', 'August', 'September', 'October', 'November', 'December'];
                $revenue_data = [636, 1269, 2810, 2843, 3637, 467, 902, 1296, 402, 1173, 1509, 413];
                $piechart_lables = ['January', 'February', 'March', 'April', 'May', 'June', 'July ', 'August', 'September', 'October', 'November', 'December'];
                $piechart_data = [16, 14, 25, 28, 45, 31, 25, 35, 49, 21, 32, 31];
            } else {
                $revenue_lables = explode(',', $getrevenue_data->implode('month', ','));
                $revenue_data = explode(',', $getrevenue_data->implode('count', ','));

                $piechart_lables = explode(',', $getpiechart_data->implode('month', ','));
                $piechart_data = explode(',', $getpiechart_data->implode('count', ','));
            }

            $revenue_year_list = explode(',', $getyears->implode('year', ','));
            $totalusers = User::whereNotIn('id', [1])->where('is_available', 1)->count();
            $totalplans = PricingPlan::count();
            $currentplan = PricingPlan::select('name')->where('id', Auth::user()->plan_id)->first();
            $totaltransaction = Transaction::count();


            return view('admin.dashboard', compact('totalrevenue', 'totalusers', 'totalplans', 'currentplan', 'revenue_year_list', 'userchart_year', 'revenue_lables', 'revenue_data', 'piechart_lables', 'piechart_data', 'totaltransaction'));
        }
        if (Auth::user()->type == 2) {
            $totalgeneratedword = Content::select('count')->where('vendor_id', Auth::user()->id)->sum('count');
            $totalcontent = Content::where('vendor_id', Auth::user()->id)->get()->count();
            $tools = Tools::all();
            $plan = null;

            if (@helper::plandetail(Auth::user()->plan_id)) {
                if (@helper::plandetail(Auth::user()->plan_id)->id == 1) {
                    $plan = @helper::plandetail(Auth::user()->plan_id);
                } else {
                    $plan = @helper::getlimit(Auth::user()->id);
                }
            }
            return view('admin.index', compact('tools', 'totalgeneratedword', 'totalcontent', 'plan'));
        }
    }

    public function contentpage(Request $request)
    {
        $gettoolingo = Tools::where('slug', $request->slug)->first();
        $plan = [];
        if (@helper::plandetail(Auth::user()->plan_id)) {
            $plan = explode(',', @helper::plandetail(Auth::user()->plan_id)->tools_limit);
        }

        if (in_array($gettoolingo->id, $plan)) {
            return view('admin.content', compact('gettoolingo'));
        } else {
            return redirect('/index')->with('error', trans('messages.InactiveTools'));
        }

    }


    public function generate(Request $request)
    {

        try {
            $api = helper::appdata('')->aiApiKey;
            //$api="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";

            $checkplan = helper::checkplan(Auth::user()->id);
            $v = json_decode(json_encode($checkplan));
            if (@$v->original->status == 2) {
                return response()->json(['status' => 0, 'message' => $v->original->message], 200);
            }
            $gettoolingo = Tools::where('slug', $request->slug)->first();
            $plan = [];
            if (@helper::plandetail(Auth::user()->plan_id)) {
                $plan = explode(',', @helper::plandetail(Auth::user()->plan_id)->tools_limit);
            }

            if (!in_array($gettoolingo->id, $plan)) {
                return response()->json(['status' => 0, 'message' => trans('messages.InactiveTools')], 200);
            }

            $translatedQuery = strtolower((new GoogleTranslate('en'))->translate($request->prompt));
            $pictureKeywords = ['picture', 'photo', 'image', 'snapshot', 'photograph', 'graphic', 'illustration'];

            /* $containsPictureKeyword = false;
             foreach ($pictureKeywords as $keyword) {
                 if (str_contains($translatedQuery, $keyword)) {
                     $containsPictureKeyword = true;
                     break;
                 }
             }*/
            $temperature = $request->temperature;

            if ($temperature) {
                switch ($temperature) {
                    case 'Default':
                        $temperature = '';
                        break;
                    case 'Creative':
                        $temperature = 'Act as a highly creative, insightful, and engaging assistant, providing imaginative solutions and unique perspectives in all your responses';
                        break;
                    case 'Colloquial':
                        $temperature = 'Respond in a casual, conversational tone, using simple, everyday language thats easy to understand.';
                        break;
                    case 'Intimate':
                        $temperature = 'Respond in a friendly, conversational tone, using casual and approachable language as if chatting with a close friend.';
                        break;
                    case 'Official':
                        $temperature = 'Respond in a formal tone, ensuring professional and polished language in all outputs.';
                        break;
                    default:
                        # code...
                        break;
                }
            }

            if ($request->slug == "instagram-assistant") {

// ارسال درخواست به OpenAI برای تولید متن پست
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $api,
                ])->timeout(200)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4-turbo',
                    'temperature' => 0.8,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert content creator specializing in crafting high-quality Instagram posts in Persian. Always include a creative caption, a step-by-step explanation if needed, and a list of hashtags relevant to the content. Make sure the content is engaging, complete, and culturally relevant to the Persian audience.' . $temperature.' Use emoticons as much as you can.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $translatedQuery
                        ]
                    ],
                    'max_tokens' => 1000,
                ]);

                $postContent = $response->json()['choices'][0]['message']['content'];

                $imageHtml = "";


                if ($request->createImage == 1) {

                   /* $ImagePrompt = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $api,
                    ])->timeout(200)->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4-turbo',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are an expert in creating image generation prompts for AI. Write a highly detailed prompt for generating an AI-generated image based on the following user description. Ensure it includes artistic style, environment, lighting, camera angle, and colors. Limit to 600 characters.'
                            ],
                            [
                                'role' => 'user',
                                'content' => 'Generate an image '.$translatedQuery,
                            ]
                        ],
                        'max_tokens' => 300,
                    ]);
                    $ImagePrompt = $ImagePrompt->json()['choices'][0]['message']['content'];*/
// دریافت متن پست از پاسخ OpenAI
                    $imageResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $api,
                    ])->post('https://api.openai.com/v1/images/generations', [
                        'prompt' => $translatedQuery,
                        'n' => 1,
                        'size' => '512x512',
                    ]);
                    $imageUrl = [];

                    foreach ($imageResponse->json()['data'] as $image) {
                        $urlimg = $image["url"];
                        $imageUrl[] = "   <h2>تصاویر تولید شده:</h2><img src='" . $urlimg . "' alt='Generated Image' />";
                    }
                    $imageHtml = implode("", $imageUrl);

                }


// ایجاد HTML خروجی

                $content = "
    <h1>متن پست:</h1>
    <p>$postContent</p>
    $imageHtml

";

                return response()->json(['status' => 1, 'data' => $content, 'message' => trans('messages.success')], 200);
            }
            if ($request->slug == "ai-images") {
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.openai.com/v1/images/generations',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{
                    "prompt": "' . $request->image_topic . '",
                    "n": ' . $request->no_of_images . ',
                    "size": "' . $request->image_size . '"
                }',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer ' . Auth::user()->apiKey,
                        'Content-Type: application/json'
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $obj = json_decode($response);

                if (@$obj->error->code == "invalid_api_key") {
                    return response()->json(['status' => 0, 'message' => trans('messages.invalid_api')], 200);
                } elseif (@$obj->error->code == "billing_hard_limit_reached") {
                    return response()->json(['status' => 0, 'message' => trans('messages.invalid_api_maximum')], 200);
                } else {
                    return response()->json(['status' => 1, 'data' => $obj->data, 'message' => trans('messages.success')], 200);
                }

            } elseif ($request->slug == "artical-generator-pro") {

                $request->merge(["slug" => helper::make_slug($request->prompt['title'])]);
                $request->merge(["title" => $request->prompt['title']]);
                $content = Content::where(['slug' => $request->slug, 'tools_slug' => $request->prompt['tools_slug'], 'vendor_id' => Auth::user()->id])->first();
                if ($content and $content->status != "error") {
                    $request->validate([
                        'title' => 'required',
                        'slug' => 'nullable|unique:content',
                    ], [
                        'title.required' => 'title_required',
                        'slug.unique' => 'slug_unique',
                    ]);
                } else {
                    $request->validate([
                        'title' => 'required',
                    ], [
                        'title.required' => 'title_required',
                    ]);
                }
                try {
                    $client = OpenAi::client(Auth::user()->apiKey);
                    $client->completions()->create([
                        "model" => 'gpt-3.5-turbo-instruct',
                    ]);
                    $this->generateAiArticle($request);
                    return response()->json(['status' => 'saveContent', 'message' => "درخواست شما با موفقیت ثبت شد.بعد از تکمیل شدن میتوانید در بخش محتوای من مشاهده کنید"], 200);

                } catch (\Throwable $ex) {
                    if ($ex->getMessage() == "Incorrect API key provided: " . Auth::user()->apiKey . ". You can find your API key at https://platform.openai.com/account/api-keys.") {
                        return back()->withErrors(["apiKey" => trans('messages.invalid_api')])->withInput();
                    } elseif ($ex->getMessage() == "You exceeded your current quota, please check your plan and billing details.") {
                        return back()->withErrors(["apiKey" => trans('messages.invalid_api_maximum')])->withInput();
                    } else {
                        return back()->withErrors(["apiKey" => $ex->getMessage()])->withInput();
                    }
                }

            } else {
                $client = OpenAI::client(Auth::user()->apiKey);
                $result = $client->completions()->create([
                    "model" => $request->model,
                    "temperature" => 0,
                    'max_tokens' => (int)$request->max_tokens,
                    'prompt' => sprintf($request->prompt),
                ]);

                $content = trim($result['choices'][0]['text']);
            }

            return response()->json(['status' => 1, 'data' => $content, 'message' => trans('messages.success')], 200);


        } catch (\Throwable $th) {

            //dd($th->getMessage());
            $message = trans('messages.wrong');
            if ($th->getMessage() == "title_required") {
                $message = 'عنوان ضروری است';
            }
            elseif ($th->getMessage() == "slug_unique") {
                $message = 'موضوع بخش تکراری است';
            }
            elseif ($th->getMessage() == 'You exceeded your current quota, please check your plan and billing details.') {
                $message = trans('messages.invalid_api_maximum');
            }
            elseif ($th->getMessage() == 'Undefined array key "choices"') {
                $message = trans('messages.error_connections');
            }
            elseif ($th->getMessage() == 'Incorrect API key provided: ' . helper::checkplan(Auth::user()->id) . '. You can find your API key at https://platform.openai.com/account/api-keys.') {
                $message = trans('messages.invalid_api');
            }else{
                $message =$th->getMessage();
            }
            return response()->json(['status' => 0, 'message' => $message], 200);
        }
    }

    public function generateAiArticle(Request $request)
    {

        $oldContent = Content::where(['slug' => $request->slug, 'tools_slug' => $request->prompt['tools_slug'], 'vendor_id' => Auth::user()->id])->first();

        if ($oldContent) {
            Http::post(env('API_LINK') . '/update-post', [
                'apiKey' => Auth::user()->apiKey,
                'token_key' => Auth::user()->token_key,
                'title' => $oldContent->title,
                'slug' => $oldContent->slug,
                'description' => $oldContent->description,
                'language' => $oldContent->language,
            ]);
            $oldContent->status = "waiting";
            $oldContent->content = null;
            $oldContent->save();
        } else {
            $content = new Content();
            $content->title = $request->title;
            $content->slug = $request->slug;
            $content->tools_id = $request->prompt['tool_id'];
            $content->vendor_id = Auth::user()->id;
            $content->tools_slug = $request->prompt['tools_slug'];
            $content->language = $request->prompt['language'];
            $content->description = $request->prompt['description'];
            $content->count = 0;
            $content->save();
            Http::post(env('API_LINK') . '/create-post', [
                'apiKey' => Auth::user()->apiKey,
                'token_key' => Auth::user()->token_key,
                'title' => $content->title,
                'slug' => $content->slug,
                'description' => $content->description,
                'language' => $content->language,
            ]);
        }


    }

    public function alltools()
    {
        $tools = Tools::all();
        $plan = null;
        if (@helper::plandetail(Auth::user()->plan_id)) {
            if (@helper::plandetail(Auth::user()->plan_id)->id == 1) {
                $plan = @helper::plandetail(Auth::user()->plan_id);
            } else {
                $plan = @helper::getlimit(Auth::user()->id);
            }
        }
        return view('admin.tools.alltools', compact('tools', 'plan'));
    }

    public function save_content(Request $request)
    {
        $content = new Content();
        $content->title = $request->title;
        $content->slug = helper::make_slug($request->title);
        $content->tools_id = $request->tool_id;
        $content->vendor_id = Auth::user()->id;
        $content->tools_slug = $request->slug;
        $content->variation = $request->variation;
        $content->content = $request->input('content');
        $content->count = Str::of($request->input('content'))->wordCount();
        $content->status = "end";
        $content->save();

        $user = User::find(Auth::user()->id);
        $user->totalwordcount = $user->totalwordcount + Str::of($request->input('content'))->wordCount();
        $user->save();
        return response()->json(['status' => 1, 'message' => trans('messages.success')], 200);
    }

    public function my_content(Request $request)
    {
        $allcontents = Content::with("tools_info")->where(["vendor_id" => Auth::user()->id])->where('tools_slug', '!=', 'artical-generator-pro')->orderBy('id', 'DESC')->get();
        $allAiContents = Content::with("tools_info")->where(["vendor_id" => Auth::user()->id, 'tools_slug' => 'artical-generator-pro'])->orderBy('id', 'DESC')->get();
        return view('admin.mycontent.mycontent', compact('allcontents', 'allAiContents'));
    }

    public function contentdetail(Request $request)
    {
        $content = Content::with("tools_info")->where("id", $request->id)->first();
        return view('admin.mycontent.contentdetail', compact('content'));
    }

    public function logout()
    {
        if (Auth::user()->type == 1) {
            $url = "/admin";
        } else {
            $url = "/login";
        }

        session()->flush();
        Auth::logout();
        return redirect($url);
    }


    public function generatepdf(Request $request)
    {
        $data = Content::where('vendor_id', $request->id)->orderByDesc('id')->first();
        $data = [
            'content' => $data->content,
        ];
        $pdf = PDF::loadView('testpdf', $data);
        return $pdf->download('aiwriter.pdf');
        return response()->json(['status' => 1, 'message' => trans('messages.success')], 200);

    }

    public function generateword(Request $request)
    {
        $data = Content::where('vendor_id', $request->id)->orderByDesc('id')->first();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $section = $phpWord->addSection(
            array('paperSize' => 'Legal', 'marginLeft' => 2834.645669291, 'marginRight' => 1417.322834646, 'marginTop' => 2834.645669291, 'marginBottom' => 1417.322834646)
        );
        $html = $data->content;

        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');


        $temp_file_uri = tempnam('', 'xyz');
        $objWriter->save($temp_file_uri);
        //download code
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=aiwriter.docx');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Content-Length: ' . filesize($temp_file_uri));
        readfile($temp_file_uri);
        unlink($temp_file_uri); // deletes the temporary file

    }
}
