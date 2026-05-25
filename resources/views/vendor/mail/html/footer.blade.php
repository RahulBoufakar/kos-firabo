@isset($url)
<p style="margin: 24px 0 0; padding-top: 24px;
          border-top: 1px solid #d4e9dc;
          font-size: 12px; color: #9ca3af; text-align: center;">
    Jika tombol di atas tidak berfungsi, salin dan tempel link berikut ke browser kamu:<br>
    <a href="{{ $url }}" style="color: #2d7a56; word-break: break-all;">{{ $url }}</a>
</p>
@endisset