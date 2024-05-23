<div class="container">
    @foreach (['success', 'danger'] as $msg)
        {{--  当key存在的时候，证明我们给 session flash 闪存里面装载了一次提示信息，那么就显示提示信息  --}}
        @if ($message = session($msg))
            <div class="alert alert-{{ $msg }} alert-block">
                <button type="button" class="close" data-dismiss="alert">×</button>
                <strong>{{ $message }}</strong>
            </div>
        @endif
    @endforeach
</div>
