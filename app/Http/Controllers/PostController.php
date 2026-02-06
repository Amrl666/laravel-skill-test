<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::active()
            ->with('user')
            ->paginate(20);

        return PostResource::collection($posts);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return 'posts.create';
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $post = Post::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json($post, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = Post::active()
            ->with('user')
            ->findOrFail($id);

        return new PostResource($post);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $post = Post::findOrFail($id);

        if (! request()->user()->can('update', $post)) {
            abort(403);
        }

        return 'posts.edit';
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        if (! $request->user()->can('update', $post)) {
            abort(403);
        }

        $post->update($request->validated());

        return response()->json($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        if (! request()->user()->can('delete', $post)) {
            abort(403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.']);
    }
}
