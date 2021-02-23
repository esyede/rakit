# Mengirim Email

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Mengirim Email](#mengirim-email)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nesciunt rerum odit quo enim dignissimos velit, totam voluptates eum eligendi, sequi assumenda accusamus consequatur autem molestiae dolorum voluptatibus fuga neque. Exercitationem.


<a id="mengirim-email"></a>
## Mengirim Email

Lorem ipsum dolor sit amet, consectetur adipisicing elit. Asperiores voluptatem dolorem, natus, eaque, accusantium cum quidem rerum quia sed, temporibus aliquam aliquid culpa a eos labore enim non ad magni!

```php
$mailer = new Mailer($server, $port, $username, $password);

$mailer->read(); // aktifkan laporan baca
$mailer->delivery(); // aktifkan laporan terkirim
// $mailer->hello('EHLO'); // set hello format kustom
$mailer->to('agung@example.com', 'Agung'); // tambahkan penerima
$mailer->cc('intan@example.com', 'Intan Purnamasari'); // CC ke intan@exmaple.com
$mailer->bcc('boss@example.com', 'Boss'); // BCC ke pak bos
$mailer->replyto('angga@example.com', 'Angga Rahman'); // balas ke angga
$mailer->attach('path/to/file.jpg'); // lampirkan sebuah file
$mailer->header('X-Mailer', 'Rakit Mailer'); // tambahkan header kustom

// set data email yang hendak dikirim
$from = 'budi@example.com';
$name = 'Budi';
$subject = 'Ayo futsal!';
$body = 'Jangan lupa besok bawa <strong>botol air minum</strong>.';
$mode = Mailer::HTML; // kirim email sebagai html
// $mode = Mailer::PLAIN; // kirim email sebagai teks biasa
$message = 'Jangan lupa besok bawa botol air minum.';

// kirim emailnya
$mailer->send($from, $name, $subject, $body, $mode, $message);

// dd($mailer->logs()); // lihat catatan debug
```

> Jika dirasa kurang memadai, silahkan gunakan library yang lebih lengkap seperti
  [PHPMailer](https://github.com/PHPMailer/PHPMailer) atau
  [SwiftMailer](https://github.com/swiftmailer/swiftmailer).
