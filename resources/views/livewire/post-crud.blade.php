<?php

use function Livewire\Volt\{classWith};
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

// Using the clean inline-class style binds $this flawlessly
new class extends Component {
    use WithFileUploads;

    public $title = '';
    public $description = '';
    public $image = null;
    public $old_image = null;
    public $post_id = null;
    public $isEdit = false;

    // Fetch the list of posts dynamically
    public function with()
    {
        return [
            'posts' => Post::latest()->get(),
        ];
    }

    public function resetFields()
    {
        $this->title = '';
        $this->description = '';
        $this->image = null;
        $this->old_image = null;
        $this->post_id = null;
        $this->isEdit = false;
    }

    public function store()
    {
        $rules = [
            'title' => 'required|string|min:3',
            'description' => 'required|string',
            'image' => $this->isEdit ? 'nullable|image|max:2048' : 'required|image|max:2048',
        ];

        $this->validate($rules);

        $imagePath = $this->old_image;

        if ($this->image) {
            if ($this->old_image && Storage::disk('public')->exists($this->old_image)) {
                Storage::disk('public')->delete($this->old_image);
            }
            $imagePath = $this->image->store('posts', 'public');
        }

        Post::updateOrCreate(['id' => $this->post_id], [
            'title' => $this->title,
            'description' => $this->description,
            'image' => $imagePath,
        ]);

        session()->flash('message', $this->post_id ? 'Post Updated Successfully.' : 'Post Created Successfully.');

        $this->resetFields();
    }

    public function edit($id)
    {
        $post = Post::findOrFail($id);
        $this->post_id = $post->id;
        $this->title = $post->title;
        $this->description = $post->description;
        $this->old_image = $post->image;
        $this->isEdit = true;
    }

    public function delete($id)
    {
        $post = Post::findOrFail($id);
        if ($post->image && Storage::disk('public')->exists($post->image)) {
            Storage::disk('public')->delete($post->image);
        }
        $post->delete();

        session()->flash('message', 'Post Deleted Successfully.');
    }
};

?>

<div class="container mt-5">
    <div class="row">
        <!-- Status Flash Messages -->
        <div class="col-md-12">
            @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
        </div>

        <!-- Form Column -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ $isEdit ? 'Edit Post (Volt)' : 'Create New Post (Volt)' }}</h5>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="store">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" wire:model="title">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" rows="4" wire:model="description"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" wire:model="image" key="{{ $post_id }}">
                            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            <!-- Image Previews -->
                            <div class="mt-2">
                                @if ($image)
                                <span class="text-muted d-block mb-1">New Preview:</span>
                                <img src="{{ $image->temporaryUrl() }}" class="img-thumbnail" width="120">
                                @elseif($old_image)
                                <span class="text-muted d-block mb-1">Current Image:</span>
                                <img src="{{ asset('storage/' . $old_image) }}" class="img-thumbnail" width="120">
                                @endif
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            @if($isEdit)
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="resetFields">Cancel</button>
                            @endif
                            <button type="submit" class="btn btn-success btn-sm">
                                {{ $isEdit ? 'Update Post' : 'Save Post' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table Datatable Column -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Manage Posts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($posts as $post)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($post->image)
                                        <img src="{{ asset('storage/' . $post->image) }}" class="img-thumbnail" style="max-width: 60px; max-height: 60px;">
                                        @else
                                        <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $post->title }}</strong></td>
                                    <td>{{ Str::limit($post->description, 60) }}</td>
                                    <td>
                                        <button wire:click="edit({{ $post->id }})" class="btn btn-primary btn-sm me-1">Edit</button>
                                        <button wire:click="delete({{ $post->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()" class="btn btn-danger btn-sm">Delete</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No records found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>