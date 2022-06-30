<?php
    namespace App\Http\Controllers\Api\Site;
    use App\Helpers\SiteHelper;
    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;

    class ContentApiController extends Controller
    {

   

        public function get(Request $request){
            $key = $request->get('key');
            $default = $request->get('default') ?? "Placeholder Text";
            $vars = $request->get('vars') ?? [];
            $type = $request->get('type') ?? "string";
            $content = SiteHelper::__c($key, $default, $vars, true, $type, true);
            return response()->json(['content' => $content]);
        }

        public function store(Request $request){

        }
    }
