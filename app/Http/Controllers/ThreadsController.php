<?php

namespace App\Http\Controllers;

use App\Channel;
use App\Thread;
use App\Filters\ThreadFilters;
use App\Rules\SpamFree;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ThreadsController extends Controller
{
    /**
     * Create a new ThreadsController instance.
     */
    public function  __construct() {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Channel $channel
     * @param  \App\Filters\ThreadFilter $filters
     * @return \Illuminate\Http\Response
     */
    public function index(Channel $channel, ThreadFilters $filters)
    {
        $threads = $this->getThreads($channel, $filters);
        $trending = array_map('json_decode', Redis::zrevrange('trending_threads', 0, 4));
        return request()->wantsJson() ? $threads : view('threads.index', compact('threads', 'trending'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('threads.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => ['required', new SpamFree],
            'body'  => ['required', new SpamFree],
            'channel_id' => 'required|exists:channels,id'
        ]);
        $thread = Thread::create([
            'user_id' => auth()->id(),
            'channel_id' => request('channel_id'),
            'title' => request('title'),
            'body' => request('body')
        ]);
        return redirect($thread->path())
            ->with('flash', 'Your thread has been published!');
    }

    /**
     * Display the specified resource.
     *
     * @param  integer $channel
     * @param  \App\Thread $thread
     * @return \Illuminate\Http\Response
     */
    public function show($channel, Thread $thread)
    {
        if (auth()->check()) {
            auth()->user()->read($thread);
        }

        Redis::zincrby('trending_threads', 1, json_encode([
            'title' => $thread->title,
            'path'  => $thread->path()
        ]));

        return view('threads.show', compact('thread'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function edit(Thread $thread)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Thread $thread)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  integer $channel
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function destroy($channel, Thread $thread)
    {
        $this->authorize('update', $thread);
        $thread->delete();
        return request()->wantsJson() ? response([], 204) : redirect('/threads');
    }

    /**
     * Get The threads filtered and by channel if exists
     *
     * @param  \App\Channel $channel
     * @param  \App\Filters\ThreadFilter $filters
     * @return \Illuminate\Http\Response
     */
    protected function getThreads(Channel $channel, ThreadFilters $filters) {
        $threads = Thread::filter($filters)->latest();
        return ($channel->exists ? $threads->where('channel_id', $channel->id) : $threads)
            ->paginate(25); 
    }
}
