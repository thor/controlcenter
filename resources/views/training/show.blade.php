@extends('layouts.app')

@section('title', 'Training')
@section('title-extension')
    @can('close', $training)
        <a href="{{ route('training.close', $training->id) }}" onclick="return confirm('Are you sure you want to close your training?')" class="btn btn-danger btn-sm">Close my training</a>
    @endcan

@endsection
@section('content')

@if($training->status < -1 && $training->status != -3)
    <div class="alert alert-warning" role="alert">
        <b>Training is closed with reason: </b>
        @if(isset($training->closed_reason))
            {{ $training->closed_reason }}
        @else
            No reason given
        @endif
    </div>
@endif

@if($training->status == -3)
    <div class="alert alert-warning" role="alert">
        <b>Training closed by student</b>
    </div>
@endif

<div id="otl-alert" class="alert alert-info" style="display: none" role="alert">
    <b id="otl-type"></b><br>
    <i class="fa fa-clock"></i>&nbsp;Valid for 7 days<br>
    <i class="fa fa-link"></i>&nbsp;<a id="otl-link" href=""></a>&nbsp;<button type="button" id="otl-link-copy-btn" class="btn btn-sm"><i class="fas fa-copy"></i></button>
</div>

<div class="row">
    <div class="col-xl-3 col-md-12 col-sm-12 mb-12">
        <div class="card shadow mb-2">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-flag"></i>&nbsp;{{ $training->user->firstName }}'s training for
                    @foreach($training->ratings as $rating)
                        @if ($loop->last)
                            {{ $rating->name }}
                        @else
                            {{ $rating->name . " + " }}
                        @endif
                    @endforeach
                </h6>

                @if(\Auth::user()->can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_REPORT_TYPE]) || \Auth::user()->can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE]))
                    <div class="dropdown" style="display: inline;">
                        <button class="btn btn-light btn-icon dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-link"></i>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_REPORT_TYPE])
                                <button class="dropdown-item" id="getOneTimeLinkReport">Create report one-time link</button>
                            @endif
                            @can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE])
                                <button class="dropdown-item" id="getOneTimeLinkExam">Create examination one-time link</button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-body">
                @can('edit', [\App\Models\Training::class, $training])
                    <a href="{{ route('training.edit', $training->id) }}" class="btn btn-light btn-icon float-right"><i class="fas fa-pencil"></i>&nbsp;Edit request</a>       
                @endcan
                <dl class="copyable">
                    <dt>State</dt>
                    <dd><i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;{{ $statuses[$training->status]["text"] }}{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}</dd>

                    <dt>Type</dt>
                    <dd><i class="{{ $types[$training->type]["icon"] }} text-primary"></i>&ensp;{{ $types[$training->type]["text"] }}</dd>

                    <dt>Level</dt>
                    <dd class="separator pb-3">
                        @if ( is_iterable($ratings = $training->ratings->toArray()) )
                            @for( $i = 0; $i < sizeof($ratings); $i++ )
                                @if ( $i == (sizeof($ratings) - 1) )
                                    {{ $ratings[$i]["name"] }}
                                @else
                                    {{ $ratings[$i]["name"] . " + " }}
                                @endif
                            @endfor
                        @else
                            {{ $ratings["name"] }}
                        @endif
                    </dd>
               
                    <dt class="pt-2">Vatsim ID</dt>
                    <dd><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->id }}</a><button type="button" onclick="navigator.clipboard.writeText('{{ $training->user->id }}')"><i class="fas fa-copy"></i></button></dd>

                    <dt>Name</dt>
                    <dd class="separator pb-3"><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->name }}</a><button type="button" onclick="navigator.clipboard.writeText('{{ $training->user->first_name.' '.$training->user->last_name }}')"><i class="fas fa-copy"></i></button></dd>

                    <dt class="pt-2">Area</dt>
                    <dd>{{ $training->area->name }}</dd>

                    <dt>Mentor</dt>
                    <dd class="separator pb-3">{{ !empty($training->getInlineMentors()) ? $training->getInlineMentors() : '-' }}</dd>

                    <dt class="pt-2">Period</dt>
                    <dd>
                        @if ($training->started_at == null && $training->closed_at == null)
                            Training not started
                        @elseif ($training->closed_at == null)
                            {{ $training->started_at->toEuropeanDate() }} -
                        @elseif ($training->started_at != null)
                            {{ $training->started_at->toEuropeanDate() }} - {{ $training->closed_at->toEuropeanDate() }}
                        @else
                            N/A
                        @endif
                    </dd>

                    <dt>Applied</dt>
                    <dd>{{ $training->created_at->toEuropeanDate() }}</dd>

                    <dt>Closed</dt>
                    <dd>
                        @if ($training->closed_at != null)
                            {{ $training->closed_at->toEuropeanDate() }}
                        @else
                            -
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        @can('update', $training)
            <div class="card shadow mb-4">
                
                <div class="card-body">
                    <form action="{{ route('training.update.details', ['training' => $training->id]) }}" method="POST">
                        @method('PATCH')
                        @csrf

                        <div class="form-group">

                            @if($activeTrainingInterest)
                                <div class="alert alert-warning" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i>&nbsp;This training has an active interest request pending.
                                </div>
                            @endif

                            <label for="trainingStateSelect">Select training state</label>
                            <select class="form-control" name="status" id="trainingStateSelect" @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
                                @foreach($statuses as $id => $data)
                                    @if($data["assignableByStaff"])
                                        @if($id == $training->status)
                                            <option value="{{ $id }}" selected>{{ $data["text"] }}</option>
                                        @else
                                            <option value="{{ $id }}">{{ $data["text"] }}</option>
                                        @endif
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" id="closedReasonInput" style="display: none">
                            <label for="trainingCloseReason">Closed reason</label>
                            <input type="text" id="trainingCloseReason" class="form-control" name="closed_reason" placeholder="{{ $training->closed_reason }}" maxlength="65">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check1" name="paused_at" {{ $training->paused_at ? "checked" : "" }} @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
                            <label class="form-check-label" for="check1">
                                Paused
                                @if(isset($training->paused_at))
                                    <span class='badge badge-danger'>{{ \Carbon\Carbon::create($training->paused_at)->diffForHumans(['parts' => 2]) }}</span>
                                @endif
                            </label>
                        </div>

                        <hr>

                        @if (\Auth::user()->isModeratorOrAbove())
                        <div class="form-group">
                            <label for="assignMentors">Assigned mentors: <span class="badge badge-dark">Ctrl/Cmd+Click</span> to select multiple</label>
                            <select multiple class="form-control" name="mentors[]" id="assignMentors">
                                @foreach($trainingMentors as $mentor)
                                    <option value="{{ $mentor->id }}" {{ ($training->mentors->contains($mentor->id)) ? "selected" : "" }}>{{ $mentor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary">Save</button>

                    </form>
                </div>
            </div>
        @endcan

    </div>

    <div class="col-xl-4 col-md-6 col-sm-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Timeline
                </h6>
            </div>
            @can('comment', [\App\Models\TrainingActivity::class, \App\Models\Training::find($training->id)])
                <form action="{{ route('training.activity.comment') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <input type="hidden" name="training_id" value="{{ $training->id }}">
                        <input type="hidden" name="update_id" id="activity_update_id" value="">
                        <input type="text" name="comment" id="activity_comment" class="form-control border" placeholder="Your internal comment ..." maxlength="512">
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary" id="activity_button" type="submit">Comment</button>
                        </div>
                    </div>
                </form>
            @endcan
            <div class="timeline">
                <ul class="sessions">
                    @foreach($activities as $activity)
                        @can('view', [\App\Models\TrainingActivity::class, \App\Models\Training::find($training->id), $activity->type])
                            <li data-id="{{ $activity->id }}">
                                <div class="time">
                                    @if($activity->type == "STATUS" || $activity->type == "TYPE")
                                        <i class="fas fa-right-left"></i>
                                    @elseif($activity->type == "MENTOR")
                                        @if($activity->new_data)
                                            <i class="fas fa-user-plus"></i>
                                        @elseif($activity->old_data)
                                            <i class="fas fa-user-minus"></i>
                                        @endif
                                    @elseif($activity->type == "PAUSE")
                                        <i class="fas fa-circle-pause"></i>
                                    @elseif($activity->type == "ENDORSEMENT")
                                        <i class="fas fa-check-square"></i>
                                    @elseif($activity->type == "COMMENT")
                                        <i class="fas fa-comment"></i>
                                    @endif
                                    
                                    @isset($activity->triggered_by_id)
                                        {{ \App\Models\User::find($activity->triggered_by_id)->name }} —
                                    @endisset

                                    {{ $activity->created_at->toEuropeanDateTime() }}
                                    @can('comment', [\App\Models\TrainingActivity::class, \App\Models\Training::find($training->id)])
                                        @if($activity->type == "COMMENT" && now() <= $activity->created_at->addDays(1))
                                            <button class="btn btn-sm float-right" onclick="updateComment({{ $activity->id }}, '{{ $activity->comment }}')"><i class="fas fa-pencil"></i></button>
                                        @endif
                                    @endcan
                                </div>
                                <p> 

                                    @if($activity->type == "STATUS")
                                        @if(($activity->new_data == -2 || $activity->new_data == -4) && isset($activity->comment))
                                            Status changed from <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                        to <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                        with reason <span class="badge badge-light">{{ $activity->comment }}</span>
                                        @else
                                            Status changed from <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                        to <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                        @endif
                                    @elseif($activity->type == "TYPE")
                                        Training type changed from <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$types[$activity->old_data]["text"] }}</span>
                                        to <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$types[$activity->new_data]["text"] }}</span>
                                    @elseif($activity->type == "MENTOR")
                                        @if($activity->new_data)
                                            <span class="badge badge-light">{{ \App\Models\User::find($activity->new_data)->name }}</span> assigned as mentor
                                        @elseif($activity->old_data)
                                        <span class="badge badge-light">{{ \App\Models\User::find($activity->old_data)->name }}</span> removed as mentor
                                        @endif
                                    @elseif($activity->type == "PAUSE")
                                        @if($activity->new_data)
                                            Training paused
                                        @else
                                            Training unpaused
                                        @endif
                                    @elseif($activity->type == "ENDORSEMENT")
                                        @if(\App\Models\Endorsement::find($activity->new_data) !== null)
                                            @empty($activity->comment)
                                                <span class="badge badge-light">
                                                    {{ str(\App\Models\Endorsement::find($activity->new_data)->type)->lower()->ucfirst() }} endorsement
                                                </span> granted, valid to 
                                                <span class="badge badge-light">
                                                    @isset(\App\Models\Endorsement::find($activity->new_data)->valid_to)
                                                        {{ \App\Models\Endorsement::find($activity->new_data)->valid_to->toEuropeanDateTime() }}
                                                    @else
                                                        Forever
                                                    @endisset
                                                </span>
                                            @else
                                                <span class="badge badge-light">
                                                    {{ str(\App\Models\Endorsement::find($activity->new_data)->type)->lower()->ucfirst() }} endorsement
                                                </span> granted, valid to 
                                                <span class="badge badge-light">
                                                    @isset(\App\Models\Endorsement::find($activity->new_data)->valid_to)
                                                        {{ \App\Models\Endorsement::find($activity->new_data)->valid_to->toEuropeanDateTime() }}
                                                    @else
                                                        Forever
                                                    @endisset
                                                </span>
                                                for positions: 
                                                @foreach(explode(',', $activity->comment) as $p)
                                                    <span class="badge badge-light">{{ $p }}</span>
                                                @endforeach
                                            @endempty
                                        @endif
                                    @elseif($activity->type == "COMMENT")
                                        {!! nl2br($activity->comment) !!}

                                        @if($activity->created_at != $activity->updated_at)
                                            <span class="text-muted">(edited)</span>
                                        @endif
                                    @endif

                                </p>
                            </li>
                        @endcan
                    @endforeach
                    <li>
                        <div class="time">
                            <i class="fas fa-flag"></i>
                            {{ $training->created_at->toEuropeanDateTime() }}
                        </div>
                        <p> 
                            Training created
                        </p>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Application
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="card bg-light mb-3">
                    <div class="card-body">

                        @if($training->english_only_training)
                            <i class="fas fa-flag-usa"></i>&nbsp;&nbsp;Requesting training in English only<br>
                        @else
                            <i class="fas fa-flag"></i>&nbsp;&nbsp;Requesting training in local language or English<br>
                        @endif

                        @isset($training->experience)
                            <i class="fas fa-book"></i>&nbsp;&nbsp;{{ $experiences[$training->experience]["text"] }}
                        @endisset

                    </div>
                </div>
            </div>

            <div class="p-4">
                <p class="font-weight-bold text-primary">
                    <i class="fas fa-envelope-open-text"></i>&nbsp;Letter of motivation
                </p>

                @if(empty($training->motivation))
                    <p><i>Not provided / relevant</i></p>
                @else
                    <p>{{ $training->motivation }}</p>
                @endif
            </div>
        </div>

    </div>

    <div class="col-xl-5 col-md-6 col-sm-12 mb-12">

        <div class="card shadow mb-4 ">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">

                @if($training->status >= 1 && $training->status <= 3)
                    <h6 class="m-0 font-weight-bold text-white">
                @else
                    <h6 class="m-0 mt-1 mb-2 font-weight-bold text-white">
                @endif
                    Training Reports
                </h6>

                @if($training->status >= 1 && $training->status <= 3)
                    <div class="dropdown" style="display: inline;">
                        <button class="btn btn-light btn-icon dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-plus"></i>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('create', [\App\Models\TrainingReport::class, $training])
                                @if($training->status >= 1)
                                    <a class="dropdown-item" href="{{ route('training.report.create', ['training' => $training->id]) }}">Training Report</a>
                                @endif
                            @else
                                <a class="dropdown-item disabled" href="#"><i class="fas fa-lock"></i>&nbsp;Training Report</a>
                            @endcan

                            @can('create', [\App\Models\TrainingExamination::class, $training])
                                @if($training->status == 3)
                                    <a class="dropdown-item" href="{{ route('training.examination.create', ['training' => $training->id]) }}">Exam Report</a>
                                @endif
                            @else
                                <a class="dropdown-item disabled" href="#"><i class="fas fa-lock"></i>&nbsp;Exam Report</a>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-body p-0">

                @can('viewAny', [\App\Models\TrainingReport::class, $training])
                    <div class="accordion" id="reportAccordion">
                        @if ($reportsAndExams->count() == 0)
                            <div class="card-text text-primary p-3">
                                No training reports yet.
                            </div>
                        @else

                            @foreach($reportsAndExams as $reportModel)
                                @if(is_a($reportModel, '\App\Models\TrainingReport'))

                                    @if(!$reportModel->draft || $reportModel->draft && \Auth::user()->isMentorOrAbove())

                                        @php
                                            $uuid = "instance-".Ramsey\Uuid\Uuid::uuid4();
                                        @endphp

                                        <div class="card">
                                            <div class="card-header p-0">
                                                <h5 class="mb-0">
                                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#{{ $uuid }}" aria-expanded="true">
                                                        <i class="fas fa-fw fa-chevron-right mr-2"></i>{{ $reportModel->report_date->toEuropeanDate() }}
                                                        @if($reportModel->draft)
                                                            <span class='badge badge-danger'>Draft</span>
                                                        @endif
                                                    </button>
                                                </h5>
                                            </div>

                                            <div id="{{ $uuid }}" class="collapse" data-parent="#reportAccordion">
                                                <div class="card-body">

                                                    <small class="text-muted">
                                                        @if(isset($reportModel->position))
                                                            <i class="fas fa-map-marker-alt"></i> {{ $reportModel->position }}&emsp;
                                                        @endif
                                                        <i class="fas fa-user-edit"></i> {{ isset(\App\Models\User::find($reportModel->written_by_id)->name) ? \App\Models\User::find($reportModel->written_by_id)->name : "Unknown"  }}
                                                        @can('update', $reportModel)
                                                            <a class="float-right" href="{{ route('training.report.edit', $reportModel->id) }}"><i class="fa fa-pen-square"></i> Edit</a>
                                                        @endcan
                                                    </small>

                                                    <div class="mt-2" id="markdown-content">
                                                        @markdown($reportModel->content)
                                                    </div>

                                                    @if(isset($reportModel->contentimprove) && !empty($reportModel->contentimprove))
                                                        <hr>
                                                        <p class="font-weight-bold text-primary">
                                                            <i class="fas fa-clipboard-list-check"></i>&nbsp;Areas to improve
                                                        </p>
                                                        <div id="markdown-improve">
                                                            @markdown($reportModel->contentimprove)
                                                        </div>
                                                    @endif

                                                    @if($reportModel->attachments->count() > 0)
                                                        <hr>
                                                        @foreach($reportModel->attachments as $attachment)
                                                            <div>
                                                                <a href="{{ route('training.object.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                                                    <i class="fa fa-file"></i>&nbsp;{{ $attachment->file->name }}
                                                                </a>
                                                            </div>
                                                        @endforeach
                                                    @endif

                                                </div>
                                            </div>
                                        </div>

                                    @endif


                                @else


                                    @php
                                        $uuid = "instance-".Ramsey\Uuid\Uuid::uuid4();
                                    @endphp

                                    <div class="card">
                                        <div class="card-header p-0">
                                            <h5 class="mb-0 bg-lightorange">
                                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#{{ $uuid }}" aria-expanded="true">
                                                    <i class="fas fa-fw fa-chevron-right mr-2"></i>{{ $reportModel->examination_date->toEuropeanDate() }}
                                                </button>
                                            </h5>
                                        </div>

                                        <div id="{{ $uuid }}" class="collapse" data-parent="#reportAccordion">
                                            <div class="card-body">

                                                <small class="text-muted">
                                                    @if(isset($reportModel->position))
                                                        <i class="fas fa-map-marker-alt"></i> {{ \App\Models\Position::find($reportModel->position_id)->callsign }}&emsp;
                                                    @endif
                                                    <i class="fas fa-user-edit"></i> {{ isset(\App\Models\User::find($reportModel->examiner_id)->name) ? \App\Models\User::find($reportModel->examiner_id)->name : "Unknown" }}
                                                    @can('delete', [\App\Models\TrainingExamination::class, $reportModel])
                                                        <a class="float-right" href="{{ route('training.examination.delete', $reportModel->id) }}" onclick="return confirm('Are you sure you want to delete this examination?')"><i class="fa fa-trash"></i> Delete</a>
                                                    @endcan
                                                </small>

                                                <div class="mt-2">
                                                    @if($reportModel->result == "PASSED")
                                                        <span class='badge badge-success'>PASSED</span>
                                                    @elseif($reportModel->result == "FAILED")
                                                        <span class='badge badge-danger'>FAILED</span>
                                                    @elseif($reportModel->result == "INCOMPLETE")
                                                        <span class='badge badge-primary'>INCOMPLETE</span>
                                                    @elseif($reportModel->result == "POSTPONED")
                                                        <span class='badge badge-warning'>POSTPONED</span>
                                                    @endif
                                                </div>

                                                @if($reportModel->attachments->count() > 0)
                                                    @foreach($reportModel->attachments as $attachment)
                                                        <div>
                                                            <a href="{{ route('training.object.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                                                <i class="fa fa-file"></i>&nbsp;{{ $attachment->file->name }}
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                @endif
                            
                            
                            @endforeach
                        @endif
                    </div>
                @else
                    <div class="card-text text-primary p-3">
                        You don't have access to see the training reports.
                    </div>
                @endcan

            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Training Interest Confirmations
                </h6>
            </div>
            <div class="card-body {{ $trainingInterests->count() == 0 ? '' : 'p-0' }}">

                @if($trainingInterests->count() == 0)
                    <p class="mb-0">No confirmation history</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Interest sent</th>
                                    <th>Confirmation Deadline</th>
                                    <th>Interest confirmed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trainingInterests as $interest)
                                <tr>
                                    <td>
                                        {{ $interest->created_at->toEuropeanDate() }}
                                    </td>
                                    <td>
                                        {{ $interest->deadline->toEuropeanDate() }}
                                    </td>
                                    <td>
                                        @if($interest->confirmed_at)
                                            <i class="fas fa-check text-success"></i>&nbsp;{{ $interest->confirmed_at->toEuropeanDate() }}
                                        @elseif($interest->expired)
                                            @if($interest->expired == 1)
                                                <i class="fas fa-times text-warning"></i>&nbsp;Invalidated
                                            @else
                                                <i class="fas fa-times text-danger"></i>&nbsp;Not confirmed
                                            @endif
                                        @else
                                            <i class="fas fa-hourglass text-warning"></i>&nbsp;Awaiting confirmation
                                        @endif

                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
  
    </div>
</div>


@endsection

@section('js')

    <!-- One Time Links -->
    <script type="text/javascript">

        // Generate a one time report link
        $('#getOneTimeLinkReport').click(async function (event) {
            event.preventDefault();
            $(this).prop('disabled', true);
            let route = await getOneTimeLink('{!! \App\Models\OneTimeLink::TRAINING_REPORT_TYPE !!}');
            $(this).prop('disabled', false);

            document.getElementById('otl-alert').style.display = "block";
            document.getElementById('otl-type').innerHTML = "Training Report one-time link";
            document.getElementById('otl-link').href = route
            document.getElementById('otl-link').innerHTML = route
            document.getElementById('otl-link-copy-btn').onclick = function(){navigator.clipboard.writeText(route)}
        });

        // Generate a one time exam report link
        $('#getOneTimeLinkExam').click(async function (event) {
            event.preventDefault();
            $(this).prop('disabled', true);
            let route = await getOneTimeLink('{!! \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE !!}');
            $(this).prop('disabled', false);document.getElementById('otl-link-copy-btn').onclick = function(){console.log(route); navigator.clipboard.writeText(route)}

            document.getElementById('otl-alert').style.display = "block";
            document.getElementById('otl-type').innerHTML = "Examination Report";
            document.getElementById('otl-link').href = route
            document.getElementById('otl-link').innerHTML = route
            document.getElementById('otl-link-copy-btn').onclick = function(){navigator.clipboard.writeText(route)}
        });

        async function getOneTimeLink(type) {
            return '{!! env('APP_URL') !!}' + '/training/onetime/' + await getOneTimeLinkKey(type);
        }

        async function getOneTimeLinkKey(type) {
            let key, result;
            result = await $.ajax('{!! route('training.onetimelink.store', ['training' => $training]) !!}', {
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{!! csrf_token() !!}"
                },
                data: {
                    'type': type
                },
                success: function (response) {
                    return response;
                },
                error: function (response) {
                    console.error(response);
                    alert("An error occured while trying to generate the one-time link.");
                }
            });

            try {
                key = JSON.parse(result).key
            } catch (error) {
                console.error(error);
            }

            return key;
        }

        function updateComment(id, oldText){
            document.getElementById('activity_update_id').value = id
            document.getElementById('activity_comment').value = oldText
            document.getElementById('activity_button').innerHTML = 'Update'
        }

    </script>

    <!-- Training report accordian -->
    <script>
        $(document).ready(function(){
            // Add minus icon for collapse element which is open by default
            $(".collapse.show").each(function(){
                $(this).prev(".card-header").find(".fas").addClass("fa-chevron-down").removeClass("fa-chevron-right");
            });

            // Toggle plus minus icon on show hide of collapse element
            $(".collapse").on('show.bs.collapse', function(){
                $(this).prev(".card-header").find(".fas").removeClass("fa-chevron-right").addClass("fa-chevron-down");
            }).on('hide.bs.collapse', function(){
                $(this).prev(".card-header").find(".fas").removeClass("fa-chevron-down").addClass("fa-chevron-right");
            });

            // Closure reason input
            toggleClosureReasonField($('#trainingStateSelect').val())

            $('#trainingStateSelect').on('change', function () {
                toggleClosureReasonField($('#trainingStateSelect').val())
            });

            function toggleClosureReasonField(val){
                if(val == -2){
                    $('#closedReasonInput').slideDown(100)
                } else {
                    $('#closedReasonInput').hide()
                }
            }

            $("#markdown-content").children("p").children("a").attr('target','_blank');
            $("#markdown-improve").children("p").children("a").attr('target','_blank');
        });
    </script>
@endsection
