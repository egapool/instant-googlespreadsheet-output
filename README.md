# instant-googlespreadsheet-output
PHPからさくっとGoogleSpreadSheetを新規作成してデータの挿入をやるやつ

## 動機
Webシステムの集計データとか調査データをさくっと他の人に共有したいときにスプレッドシートに直で吐き出したら楽だと思ったので。

スプレッドシートに吐き出すライブラリはあるにはあるけど、先に手動でシートを作ってからみたいのが多くて、欲しかったのはデータの配列突っ込んだらスプレッドシートのURLを返すというそれだけのシンプルなものなので作りました。

いつのまにかGoogleAnalyticsで集計データのアウトプット先にスプレッドシートが追加されていて、便利だなーと思ったのが発端です。（以前はCSVダウンロードとかだけだった気がします。）


## 準備
GoogleApisのサービスアカウントが必要（そのうち書く）


## 使い方

```
$data = [
	['日','月','火','水','木','金','土'],
	['あれ','これ','それ','これ？','どれ？','それ','えっ'],
];

// create instanse
$outputer = new InstantGoogleSpreadSheetOutput('/path/to/youre/spreadsheet-xxxxxxxxx.json');

// create new SpreadSheet
$outputer
	->creatSheet() // create new SpreadSheet
	->write($data) // insert data
	->attatchAuthToUser('test[at]gmail.com'); // permit others to access this sheet

echo $outputer->spreadsheet->spreadsheetUrl;
```

## やること

* composerでinstallできるように体裁整える
* リポジトリ名がなんかださい