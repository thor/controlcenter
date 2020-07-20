@extends('layouts.app')

@section('title', 'Training Report')
@section('content')


<div class="row">
    <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    {{ $report->training->user->firstName }}'s training {{ $report->created_at->toFormattedDateString() }}{{ $report->draft ? " (DRAFT)" : "" }}
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('training.report.update', ['report' => $report->id]) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input 
                            id="position"
                            class="form-control @error('position') is-invalid @enderror"
                            type="text"
                            name="position"
                            list="positions"
                            value="{{ empty(old('position')) ? $report->position : old('position')}}"
                            required>

                        <datalist id="positions">
                            @foreach($positions as $position)
                                <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                            @endforeach
                        </datalist>

                        @error('position')
                            <span class="text-danger">{{ $errors->first('position') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="contentBox">Report</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" name="content" id="contentBox" rows="8" placeholder="Write the report here.">{{ empty(old('content')) ? $report->content : old('content') }}</textarea>
                        @error('content')
                            <span class="text-danger">{{ $errors->first('content') }}</span>
                        @enderror
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input @error('draft') is-invalid @enderror" id="draftCheck" {{ $report->draft ? "checked" : "" }}>
                        <label class="form-check-label" name="draft" for="draftCheck">Save as draft</label>
                        @error('draft')
                            <span class="text-danger">{{ $errors->first('draft') }}</span>
                        @enderror
                    </div>

                    @if (\Illuminate\Support\Facades\Gate::inspect('update', $report)->allowed())
                        <button type="submit" class="btn btn-success">Update report</button>
                    @endif

                    @if (\Illuminate\Support\Facades\Gate::inspect('delete', $report)->allowed())
                        <a href="#" class="btn btn-danger" id="delete-btn" data-report="{{ $report->id }}">Delete report</a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Manage attachments
                </h6>
            </div>
            <div class="card-body">

                <div>
                    @if(count($report->attachments) == 0)
                        <i>This report has no attachments.</i>
                    @endif

                    @foreach($report->attachments as $attachment)
                        <div data-id="{{ $attachment->id }}">
                            <a href="{{ route('training.report.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                {{ $attachment->file->name }}
                            </a>
                            <i data-attachment="{{ $attachment->id }}" onclick="deleteAttachment(this)" class="fa fa-lg fa-trash text-danger"></i>
                        </div>
                    @endforeach
                </div>

                <hr>

                <form method="post" id="file-form" action="{{ route('training.report.attachment.store', ['report' => $report->id]) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="attachments">Attachments</label>
                        <div>
                            <input type="file" name="file" id="add-file" class="@error('file') is-invalid @enderror" accept=".pdf, .xls, .xlsx, .doc, .docx, .txt, .png, .jpg, .jpeg" onchange="uploadFile(this)" multiple>
                        </div>
                        @error('file')
                            <span class="text-danger">{{ $errors->first('file') }}</span>
                        @enderror
                    </div>

                </form>
                
            </div>
        </div>
    </div>
</div>


@endsection

@section('js')
<script type="text/javascript">

    function uploadFile(input) {
        if (input.value != null) {
            $("#file-form").submit();
        }
    }

    function deleteAttachment(input) {

        input = $(input);
        let id = input.data('attachment');

        $.ajax('/training/report/attachment/' + id, {
            'method' : "post",
            'data' : {
                '_token': "{!! csrf_token() !!}",
                '_method': 'delete'
            },
            success: function (data) {
                if (data.message === 'Attachment successfully deleted') {
                    $("div[data-id="+id+"]").remove();
                }
            },
            error: function (data) {
                console.log("An error occurred while attempting to delete attachment.");
            }
        });

    }

</script>
@endsection
