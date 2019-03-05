@extends ('layouts.master')

@section('head')
<style>
    .table > tbody > tr > td {
         vertical-align: middle !important;
         max-width: 90px;
         overflow: hidden;
    }

    .table > tbody >tr > td.structure {
        max-width: 100px;
        overflow: hidden;
    }

    .table > tbody >tr > td.alpha {
        max-width: 200px;
        overflow: hidden;
    }

    .table > tbody >tr > td.mass {
        max-width: 70px;
        overflow: hidden;
    }

</style>
@endsection

@section ('content')

@include ('layouts.navbar')

<div class="container-mx-auto">
    <div class="title">
        <h1> Reactions of {{ $user->name }} </h1>
    </div>

    @if (! $user->prefix)
        <form class="form-inline" action="/userlabel" method="POST">
            {{ @csrf_field() }}
            {{ @method_field('patch') }}
            
            <div style="padding-bottom: 30px; text-align:center;">
                <p>Before you can start adding new reactions, please speficy your desired prefix below.</p>

                <div class="form-group">
                  <label for="prefix">Prefix</label>
                  <input name="prefix" type="text" class="form-control" id="prefix" placeholder="JBN">
                </div>

                <button type="submit" class="btn btn-success">Save</button>
            </div>

        </form>

    @else 

    <div class="table-responsive">
        <table class="table" style="height: 100%">
            <thead>
                <tr>
                    <th>
                        Date
                    </th>
                    <th>
                        Label
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach($projects as $project)
                <tr style="height:100%;" class="info">
                    <td colspan="14" style="text-align:center">
                        <strong title="{{ $project->description }}"> {{ $project->name }} </strong><br>
                        <a href="/reactions/new/{{ $project->id }}">
                            <strong>&plus; Add New Reaction</strong>
                        </a>
                    </td>
                </tr>

                    @forelse ($project->reactions as $reaction)
                        <tr>
                            <td colspan="1">{{ $reaction->created_at->format('d-m-Y') }}</td>
                            <td colspan="1">{{ $reaction->label }}</td>
                            <td colspan="12">
                                @forelse ($reaction->startingMaterials as $compound)
                                  @if (! $loop->first) 
                                    + <a href="/compounds/{{ $compound->id }}"><img src="/{{ $compound->SVGPath }}"></a>
                                  @else
                                   <a href="/compounds/{{ $compound->id }}"><img src="/{{ $compound->SVGPath }}"></a>
                                  @endif
                                @empty
                                  (nothing added, yet)
                                @endforelse

                                <i class="fa fa-arrow-right" style="font-size: 50px;"></i>

                                @forelse ($reaction->products as $compound)
                                  
                                  @if (! $loop->first) 
                                    + <a href="/compounds/{{ $compound->id }}"><img src="/{{ $compound->SVGPath }}"></a>
                                  @else
                                   <a href="/compounds/{{ $compound->id }}"><img src="/{{ $compound->SVGPath }}"></a>
                                  @endif

                                @empty
                                  Add products.
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" style="text-align:center;">
                                <br>
                                Hey there!
                                <br>
                                It looks like you don't have any reactions in this project yet.
                                <br>
                                <a href="/reactions/new/{{ $project->id }}"><strong>Click here to add your first</strong></a>
                                <br>
                                <br>
                            </td>
                        </tr>
                    @endforelse
                
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

@endsection
