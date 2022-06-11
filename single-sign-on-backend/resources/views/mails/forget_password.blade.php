<div>
  <div class="card">
    <div class="card-header">
      忘記密碼
    </div>
    <div class="card-body">
      <h5 class="card-title">申請重設密碼</h5>
      <p class="card-text">
        您在 {{ $data['apply_at'] }} 申請了重設密碼<br />
        如需重設密碼，請 <a href="{{ $data['uri'] }}" class="btn btn-primary">按此重設密碼</a>
        若按鈕未被正常渲染或是無法點選，請直接複製下列網址前往密碼重設頁面: <br />
        {{ $data['uri'] }}
      </p>
    </div>
  </div>
</div>
