<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="robots" content="noindex, nofollow">
  <title>Rakit log viewer</title>
  <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
  <link rel="stylesheet" href="{{ asset('packages/logviewer/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('packages/logviewer/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('packages/logviewer/css/style.css') }}">
  <script src="{{ asset('packages/logviewer/js/theme.js') }}"></script>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-2 sidebar mb-3 ml-0">
      <h1><i class="fa fa-calendar" aria-hidden="true"></i> Rakit Log Viewer</h1>
      <p class="text-muted"><i>by <strong><a href="https://github.com/esyede" target="_blank">@esyede</a></strong></i></p>
      <div class="custom-control custom-switch" style="padding-bottom:20px;">
        <input type="checkbox" class="custom-control-input" id="darkSwitch">
        <label class="custom-control-label" for="darkSwitch" style="margin-top: 6px;">Dark mode</label>
      </div>
      <div class="list-group div-scroll">
        @foreach($folders as $folder)
          <div class="list-group-item">
            <?php \Esyede\Viewer::tree($log_dir, $structure); ?>
          </div>
        @endforeach
        @foreach($files as $file)
          <a href="?l={{ \Esyede\Viewer::encode($file) }}"
             class="list-group-item @if ($current_file === $file) llv-active @endif">
            {{ $file }}
          </a>
        @endforeach
      </div>
    </div>
    <div class="col-10 table-container">
      @if ($logs === null)
        <div>Log file >50M, please download it.</div>
      @else
        <table id="table-log" class="table table-striped" data-ordering-index="{{ $standard ? 2 : 0 }}">
          <thead>
          <tr>
            @if ($standard)
              <th class="col-md-2">Level</th>
              <th>Context</th>
              <th class="col-md-2">Date</th>
            @else
              <th>Line number</th>
            @endif
            <th>Content</th>
          </tr>
          </thead>
          <tbody>
          @foreach($logs as $key => $log)
            <tr data-display="stack{{ $key }}">
              @if ($standard)
                <td class="nowrap col-md-2 text-{{ $log['level_class'] }}">
                  <span class="fa fa-{{ $log['level_img'] }}" aria-hidden="true"></span>&nbsp;&nbsp;{{ $log['level'] }}
                </td>
                <td class="text col-md-2">{{ $log['context'] }}</td>
              @endif
              <td class="date col-md-2">{{ $log['date'] }}</td>
              <td class="text">
                @if ($log['stack'])
                  <button type="button" class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2" data-display="stack{{ $key }}">
                    <span class="fa fa-search"></span>
                  </button>
                @endif
                {{ $log['text'] }}
                @if (isset($log['in_file']))
                  <br/>{{ $log['in_file'] }}
                @endif
                @if ($log['stack'])
                  <div class="stack" id="stack{{ $key }}"
                       style="display: none; white-space: pre-wrap;">{{ trim($log['stack']) }}
                  </div>
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @endif
      <div class="p-3">
        @if($current_file)
          <a href="?dl={{ \Esyede\Viewer::encode($current_file) }}{{ ($current_folder) ? '&f='.\Esyede\Viewer::encode($current_folder) : '' }}">
            <span class="fa fa-download"></span> Download file
          </a>
          -
          <a id="clean-log" href="?clean={{ \Esyede\Viewer::encode($current_file) }}{{ ($current_folder) ? '&f='.\Esyede\Viewer::encode($current_folder) : '' }}">
            <span class="fa fa-sync"></span> Clean file
          </a>
          -
          <a id="delete-log" href="?del={{ \Esyede\Viewer::encode($current_file) }}{{ ($current_folder) ? '&f='.\Esyede\Viewer::encode($current_folder) : '' }}">
            <span class="fa fa-trash"></span> Delete file
          </a>
          @if(count($files) > 1)
            -
            <a id="delete-all-log" href="?delall=true{{ ($current_folder) ? '&f='.\Esyede\Viewer::encode($current_folder) : '' }}">
              <span class="fa fa-trash-alt"></span> Delete all files
            </a>
          @endif
        @endif
      </div>
    </div>
  </div>
</div>
<script src="{{ asset('packages/logviewer/js/jquery.slim.min.js') }}"></script>
<script src="{{ asset('packages/logviewer/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('packages/logviewer/js/font-awesome.min.js') }}"></script>
<script src="{{ asset('packages/logviewer/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('packages/logviewer/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('packages/logviewer/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('packages/logviewer/js/script.js') }}"></script>
</body>
</html>
