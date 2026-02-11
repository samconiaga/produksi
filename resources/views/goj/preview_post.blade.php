<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Preview GOJ - {{ $goj->doc_no }}</title>
</head>
<body>
  <form id="f" method="post" action="{{ route('gudang-release.lphp.print') }}">
    @csrf
    @foreach($ids as $id)
      <input type="hidden" name="ids[]" value="{{ $id }}">
    @endforeach
  </form>
  <script>document.getElementById('f').submit();</script>
</body>
</html>