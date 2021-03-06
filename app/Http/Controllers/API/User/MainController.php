<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Topic;

class MainController extends Controller
{
    public function loadArticles(){
        return  DB::table('articles')
                    ->join('topics','articles.topic_id','=','topics.topic_id')
                    ->join('masters','articles.user_id','=','masters.id')
                    ->select('articles.*','topics.topic_name','masters.name')
                    ->where('articles.article_status','=','published')
                    ->latest()
                    ->paginate(10);
    }
    
    public function loadTopics(){
        return Topic::where('topic_status','active')
                    ->orderBy('topic_name','ASC')
                    ->get(['topic_id','topic_name']);
    }
    public function search($search){
        $articles = DB::table('articles')
                        ->join('topics','articles.topic_id', '=', 'topics.topic_id')
                        ->join('masters','articles.user_id','=','masters.id')
                        ->select('articles.*','topics.topic_name','masters.name')
                        ->where(function($query) use ($search){
                            $query->where([['articles.title','LIKE',"%$search%"],['articles.article_status','=','published']])
                                ->orWhere([['topics.topic_name','LIKE',"%$search%"],['articles.article_status','=','published']])
                                ->orWhere([['articles.content','LIKE',"%$search%"],['articles.article_status','=','published']])
                                ->orWhere([['masters.name','LIKE',"%$search%"],['articles.article_status','=','published']]);
                        })->latest()->paginate(10);
        
        return $articles;
    }
    public function topicSearch($search){
        $articles = DB::table('articles')
                        ->join('topics','articles.topic_id', '=', 'topics.topic_id')
                        ->join('masters','articles.user_id','=','masters.id')
                        ->select('articles.*','topics.topic_name','masters.name')
                        ->where(function($query) use ($search){
                            $query->where([['articles.topic_id','=',$search],['articles.article_status','=','published']]);
                        })->latest()->paginate(10);
        
        return $articles;
    }

    public function view_article($article_id){
        $article =  DB::table('articles')
                ->join('topics','articles.topic_id','=','topics.topic_id')
                ->join('masters','articles.user_id','=','masters.id')
                ->select('articles.*','topics.topic_name','masters.name')
                ->where('articles.article_id','=',$article_id)
                ->first();
        
        return json_encode($article);
        
    }

    public function profile(){
        return auth('api')->user();
    }

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();
        
        $this->validate($request,[
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users,email,'.$user->id,
            'password' => 'sometimes|required|min:6'
        ]);

        if(!empty($request->password)){
            $request->merge(['password' => Hash::make($request['password'])]);
        }

        $user->update($request->all());
        return ['message' => "success"];
    }
}
