<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Article;
use App\Topic;
use App\Master;



class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(auth()->user()->role === 'author'){
            return  DB::table('articles')
                ->join('topics','articles.topic_id', '=', 'topics.topic_id')
                ->select('articles.*','topics.topic_name')
                ->where('articles.user_id','=',auth()->user()->id)
                ->latest()
                ->paginate(10);
        }else{
            return  DB::table('articles')
                    ->join('topics','articles.topic_id', '=', 'topics.topic_id')
                    ->select('articles.*','topics.topic_name')
                    ->latest()
                    ->paginate(10);
        }
        
    }
    public function get_topics(){
        return Topic::where('topic_status',1)
                    ->orderBy('topic_name','ASC')
                    ->get(['topic_id','topic_name']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'title' => 'required|string',
            'topic' => 'required',
            'content' => 'required',
            'article_status' => 'required'
        ]);
        if($request->photo){
            $extension = explode('/', mime_content_type($request->photo))[1];
            $name = time().'.'.$extension;
    
            \Image::make($request->photo)->save(public_path('img/article_photos/').$name);
            $request->merge(['photo' => $name]);
        }else{
            $request->merge(['photo' => 'article_default.png']);
        }
        return Article::create([
            'title' => $request['title'],
            'user_id' => auth()->user()->id,
            'topic_id' => $request['topic'],
            'content' => $request['content'],
            'photo' => $request['photo'],
            'article_status' => $request['article_status'] 
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'title' => 'required|string',
            'topic' => 'required',
            'content' => 'required',
            'article_status' => 'required'
        ]);
        $current_photo = $request->current_photo;
        if($request->photo != $current_photo){
            $extension = explode('/', mime_content_type($request->photo))[1];
            $name = time().'.'.$extension;

            \Image::make($request->photo)->save(public_path('img/article_photos/').$name);
            $request->merge(['photo' => $name]);

            $articlePhoto = public_path('img/article_photos/').$current_photo;
            if(!($current_photo == 'article_default.png')){
                if(file_exists($articlePhoto)){
                    @unlink($articlePhoto);
                }
            }
            
        }
        return Article::where('article_id', $id)->update([
            'title' => $request['title'],
            'topic_id' => $request['topic'],
            'content' => $request['content'],
            'photo' => $request['photo'],
            'article_status' => $request['article_status']
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        $article = Article::findOrFail($id);
        $articlePhoto = public_path('img/article_photos/').$article->photo;
        if(!($article->photo == 'article_default.png')){
            if(file_exists($articlePhoto)){
                @unlink($articlePhoto);
            }
        }
        return $article->delete();
    }
    public function search(){
        if($search = \Request::get('q')){
            if(auth()->user()->role === 'author'){
                $articles = DB::table('articles')
                                ->join('topics','articles.topic_id', '=', 'topics.topic_id')
                                ->select('articles.*','topics.topic_name')
                                ->where(function($query) use ($search){
                                    $query->where([['articles.title','LIKE',"%$search%"],['articles.user_id','=',auth()->user()->id]])
                                        ->orWhere([['topics.topic_name','LIKE',"%$search%"],['articles.user_id','=',auth()->user()->id]])
                                        ->orWhere([['articles.content','LIKE',"%$search%"],['articles.user_id','=',auth()->user()->id]]);
                                })->latest()->paginate(10);
            }else{
                $articles = DB::table('articles')
                                ->join('topics','articles.topic_id', '=', 'topics.topic_id')
                                ->select('articles.*','topics.topic_name')
                                ->where(function($query) use ($search){
                                    $query->where('articles.title','LIKE',"%$search%")
                                        ->orWhere('topics.topic_name','LIKE',"%$search%")
                                        ->orWhere('articles.content','LIKE',"%$search%");
                                })->latest()->paginate(10);
            }
        }else{
            if(auth()->user()->role === 'author'){
                $articles = DB::table('articles')
                                ->join('topics','articles.topic_id', '=', 'topics.topic_id')
                                ->select('articles.*','topics.topic_name')
                                ->where('articles.user_id','=',auth()->user()->id)
                                ->latest()
                                ->paginate(10);
            }else{
                $articles = DB::table('articles')
                                ->join('topics','articles.topic_id', '=', 'topics.topic_id')
                                ->select('articles.*','topics.topic_name')
                                ->latest()
                                ->paginate(10);
            }
        }

        return $articles;
    }
}
