@extends('layouts.app')

@section('content')
<thread-view-component :initial-replies-count="{{ $thread->replies_count }}" inline-template>
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex d-flex-row align-items-center">
                            <div class="mr-auto">
                                <a href="{{ route('profile', $thread->creator) }}">{{ $thread->creator->name }}</a> posted: {{ $thread->title }}
                            </div>
                            @can ('update', $thread)
                                <form action="{{ $thread->path() }}" method="POST">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this thread?')">Delete Thread</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">{{ $thread->body }}</div>
                </div>

                <replies-component :data="{{ $thread->replies }}" @removed="repliesCount--"></replies-component>

                {{--{{ $replies->links() }}--}}

                @if (auth()->check())
                    <form method="POST" action="{{ $thread->path() . '/replies' }}">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <textarea class="form-control" name="body" placeholder="Have something to say?" rows="5"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Post</button>
                    </form>
                @else
                    <p class="text-center">Please <a href="{{ route('login') }}">sign in</a> to participate in this discussion</p>
                @endif
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        This thread was published {{ $thread->created_at->diffForHumans() }} by 
                        <a href="{{ route('profile', $thread->creator) }}">{{ $thread->creator->name }}</a>, 
                        and currently has <span v-text="repliesCount"></span> {{ str_plural('comment', $thread->replies_count) }}.
                    </div>
                </div>
            </div>
        </div>    
    </div>
</thread-view-component>
@endsection
