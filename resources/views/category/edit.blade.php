@extends('layouts.main')
@section('title')
    {{__("Edit Categories")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('category.index') }}">< {{__("Back to All Categories")}} </a>
        </div>
        <div class="row">
            <form action="{{ route('category.update', $category_data->id) }}" method="POST" data-parsley-validate enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <input type="hidden" name="edit_data" value={{ $category_data->id }}>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">{{__("Edit Categories")}}</div>
                        <div class="card-body mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="col-md-12 form-group mandatory">
                                        <label for="name" class="mandatory form-label">{{ __('Name') }}</label>
                                        <input type="text" name="name" id="name" class="form-control" data-parsley-required="true" value="{{ $category_data->name }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="col-md-12 form-group mandatory">
                                        <label for="category_type" class="mandatory form-label">{{ __('Category Type') }}</label>
                                        <select name="type" id="category_type" class="form-select form-control" data-parsley-required="true" onchange="loadParentCategories()">
                                            <option value="service_experience" {{ $category_data->type == 'service_experience' ? 'selected' : '' }}>{{ __('Service & Experience') }}</option>
                                            <option value="providers" {{ $category_data->type == 'providers' ? 'selected' : '' }}>{{ __('Providers') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="col-md-12 form-group">
                                        <label for="p_category" class="form-label">{{ __('Parent Category') }}</label>
                                        <select name="parent_category_id" id="p_category" class="form-select form-control" data-placeholder="{{__("Select Category")}}">
                                            <option value="">{{__("Select a Category")}}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="col-md-12 form-group mandatory">
                                        <label for="slug" class="form-label">{{ __('Slug') }} <small>(English Only)</small></label>
                                        <input type="text" name="slug" id="slug" class="form-control" data-parsley-required="true" value="{{ $category_data->slug }}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="description" class="mandatory form-label">{{ __('Description') }}</label>
                                    <textarea name="description" id="description" class="form-control" cols="10" rows="5">{{ $category_data->description }}</textarea>
                                    <div class="form-check form-switch mt-3">
                                        <input type="hidden" name="status" id="status" value="{{ $category_data->status}}">
                                        <input class="form-check-input status-switch" type="checkbox" role="switch" aria-label="status" name="active" id="required" {{ $category_data->status == 1 ? 'checked' : '' }}>{{ __('Active') }}
                                        <label class="form-check-label" for="status"></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="col-md-12 form-group mandatory">
                                        <label for="Field Name" class="mandatory form-label">{{ __('Image') }}</label>
                                        <div class="cs_field_img">
                                            <input type="file" name="image" class="image" style="display: none" accept=" .jpg, .jpeg, .png, .svg">
                                            <img src="{{ empty($category_data->image) ? asset('assets/img_placeholder.jpeg') : $category_data->image }}" alt="" class="img preview-image" id="">
                                            <div class='img_input'>{{__("Browse File")}}</div>
                                        </div>
                                        <div class="input_hint"> {{__("Icon (use 256 x 256 size for better view)")}}</div>
                                        <div class="img_error" style="color:#DC3545;"></div>
                                    </div>
                                </div>

                            </div>
                            @if($languages->isNotEmpty())
                            <hr>
                            <h5>{{ __("Translation") }}</h5>
                            <div class="row">
                                @foreach($languages as $key => $language)
                                    <div class="col-md-6 form-group">
                                        <label for="name_{{$language->id}}" class="form-label">
                                            {{ ($key + 1) . ". " . $language->name }}:
                                        </label>
                                        <input name="translations[{{$language->id}}]" id="name_{{$language->id}}" class="form-control" value="{{ $translations[$language->id] ?? '' }}">
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        </div>
                    </div>
                    <div class="col-md-12 text-end">
                        <input type="submit" class="btn btn-primary" value="{{__("Save and Back")}}">
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

<!-- Make sure jQuery is loaded -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // Load parent categories when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadParentCategories({{ $category_data->parent_category_id ?? 'null' }});
    });
    
    function loadParentCategories(selectedParentId = null) {
        const typeSelect = document.getElementById('category_type');
        const parentCategorySelect = document.getElementById('p_category');
        const selectedType = typeSelect.value;
        const currentCategoryId = {{ $category_data->id }};
        
        // Clear current options except the first one
        while (parentCategorySelect.options.length > 1) {
            parentCategorySelect.remove(1);
        }
        
        // If no type is selected, return
        if (!selectedType) {
            console.log('No type selected');
            return;
        }
        
        console.log('Loading parent categories for type:', selectedType);
        console.log('Current category ID:', currentCategoryId);
        console.log('Selected parent ID:', selectedParentId);
        
        // Use fetch API
        fetch('{{ url("category/get-parent-categories") }}?type=' + selectedType)
            .then(response => response.json())
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Add options for each parent category
                    data.categories.forEach(function(category) {
                        // Skip the current category to prevent self-reference
                        if (category.id == currentCategoryId) {
                            console.log('Skipping current category:', category.name);
                            return;
                        }
                        
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        
                        // Add indentation based on level
                        if (category.level > 0) {
                            option.textContent = '- '.repeat(category.level) + option.textContent;
                        }
                        
                        // Select the parent category if it matches
                        if (selectedParentId && category.id == selectedParentId) {
                            option.selected = true;
                            console.log('Selected parent category:', category.name);
                        }
                        
                        parentCategorySelect.appendChild(option);
                    });
                    
                    if (data.categories.length === 0) {
                        console.log('No parent categories found for type:', selectedType);
                    }
                } else {
                    console.error('Error from server:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading parent categories:', error);
            });
    }
</script>
