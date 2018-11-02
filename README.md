# swpm-form-builderの送信メールバグ修正用のリポジトリです
## swpm-form-builderの問題点
- 既存コードをそのまま使用すると、下記の問題点が発生します
  - 文字化けする
  - 改行されない
  - カスタムフィールドをメール文に盛り込めない
- 本コードは上記問題点に対応したものです

- swpm-form-builderについては、下記参照
   - https://simple-membership-plugin.com/simple-membership-form-builder-addon/

## 使用方法
- `swpm-form-builder/classes/class.swpm-fb-form-builder`のsend_reg_emailがメール送信処理のロジックになります
- 本リポジトリの`class.swpm-fb-form-builder.send_reg_email.php`の内容を、上記メソッドに上書きしてください