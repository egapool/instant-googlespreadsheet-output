<?php
date_default_timezone_set('Asia/tokyo');

require_once __DIR__.'/vendor/autoload.php';

class InstantGoogleSpreadSheetOutput
{
	/**
	 * @var \Google_Client $client
	 * 
	 */
	public $client;

	/**
	 * @var \Google_Service_Sheets $service
	 * 
	 */
	public $service;

	/**
	 * @param string PATH_TO_YOURE_CREDENTIALS.json サービスアカウントの秘密鍵
	 * @return self
	 */
	public function __construct($GOOGLE_APPLICATION_CREDENTIALS)
	{
		putenv('GOOGLE_APPLICATION_CREDENTIALS='.$GOOGLE_APPLICATION_CREDENTIALS);
		$this->client = new \Google_Client();
		$this->client->useApplicationDefaultCredentials();
		$this->client->addScope(\Google_Service_Drive::DRIVE);
		$this->service = new \Google_Service_Sheets($this->client);

		return $this;
	}

	/**
	 * 新しくスプレッドシートを作成
	 * 
	 * @return \Google_Service_Sheets_Spreadsheet $response
	 */
	public function creatSheet()
	{
		// TODO: Assign values to desired properties of `requestBody`:
		$requestBody = new \Google_Service_Sheets_Spreadsheet();
		$response = $this->service->spreadsheets->create($requestBody);
		return $response;
	}

	/**
	 * スプレッドシートにデータを書き込む
	 * 
	 * @param string $spreadsheetId
	 * @param array $inputData 配列の配列
	 * @return \Google_Service_Sheets_BatchUpdateValuesResponse $resopnse
	 * 
	 */
	public function write($spreadsheetId,$inputData)
	{
		$data = [];
		$row_num = 1;
		$default_rows = 1000;

		// Increase rows necessary
		if ( count($inputData) > $default_rows)
		{
			$requests = [
				new \Google_Service_Sheets_Request([
					'appendDimension' => [
						'sheetId' => 0,
						'dimension' => 'ROWS',
						'length' => count($inputData) - $default_rows
					]
				])
			];
			$requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
				'requests' => $requests
			]);
			$response = $this->service->spreadsheets->batchUpdate($spreadsheetId, $requestBody);
		}

		// Request insert data
		foreach($inputData as $row)
		{
			if (!is_array($row)) $row = [$row];

			$data[] = new \Google_Service_Sheets_ValueRange([
				'range' => 'Sheet1!A'.$row_num,
				'values' => [$row],
			]);
			$row_num++;
		}

		$body = new \Google_Service_Sheets_BatchUpdateValuesRequest(array(
			'valueInputOption' => 'RAW',
			'data' => $data
		));

		$response = $this->service->spreadsheets_values->batchUpdate($spreadsheetId,$body);

		return $response;
	}

	/**
	 * サービスアカウント以外のユーザーにも権限を付与する（ブラウザから見たいため）
	 * 
	 * @param string $spreadsheetId https://docs.google.com/spreadsheets/d/{この部分}/edit#gid=0
	 * @param string $userGMailAddress gmailアドレス
	 * @return \Google_Service_Drive_Permission $response
	 */
	public function attatchAuthToUser($spreadsheetId,$userGMailAddress)
	{
		// Google Drive ApisのPermission resourceを作成
		$permission = new \Google_Service_Drive_Permission([
			'type'  => 'user',
			'role'  => 'writer',
			'emailAddress' => $userGMailAddress,
		]);

		// Permission resourceを割り当て
		$response = (new \Google_Service_Drive($this->client))->permissions->create($spreadsheetId,$permission);

		return $response;
	}
}