<?php

namespace InstantGoogleSpreadSheetOutput;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Sheets;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_Request;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_ValueRange;
use Google_Service_Sheets_BatchUpdateValuesRequest;
use Google_Service_Drive_Permission;

date_default_timezone_set('Asia/tokyo');

class Outputer
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
	 * @var \Google_Service_Sheets_Spreadsheet $service
	 * 
	 */
	public $spreadsheet;

	/**
	 * @param string PATH_TO_YOURE_CREDENTIALS.json サービスアカウントの秘密鍵
	 * @return self
	 */
	public function __construct($GOOGLE_APPLICATION_CREDENTIALS)
	{
		putenv('GOOGLE_APPLICATION_CREDENTIALS='.$GOOGLE_APPLICATION_CREDENTIALS);
		$this->client = new Google_Client();
		$this->client->useApplicationDefaultCredentials();
		$this->client->addScope(Google_Service_Drive::DRIVE);
		$this->service = new Google_Service_Sheets($this->client);

		return $this;
	}

	/**
	 * 新しくスプレッドシートを作成
	 * 
	 * @return self
	 */
	public function creatSheet()
	{
		// TODO: Assign values to desired properties of `requestBody`:
		$requestBody = new Google_Service_Sheets_Spreadsheet();
		$this->spreadsheet = $this->service->spreadsheets->create($requestBody);

		return $this;
	}

	/**
	 * スプレッドシートにデータを書き込む
	 * 
	 * @param array $inputData 配列の配列
	 * @return self
	 * 
	 */
	public function write($inputData)
	{
		$data = [];
		$row_num = 1;
		$default_rows = 1000;

		// Increase rows necessary
		if ( count($inputData) > $default_rows)
		{
			$requests = [
				new Google_Service_Sheets_Request([
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
			$response = $this->service->spreadsheets->batchUpdate($this->spreadsheet->spreadsheetId, $requestBody);
		}

		// Insert label row
		$data[] = new Google_Service_Sheets_ValueRange([
			'range' => 'Sheet1!A'.$row_num,
			'values' => [array_keys($inputData[0])],
		]);
		$row_num++;

		// Request insert data
		foreach($inputData as $row)
		{
			if (!is_array($row)) $row = [$row];

			$data[] = new Google_Service_Sheets_ValueRange([
				'range' => 'Sheet1!A'.$row_num,
				'values' => [array_values($row)],
			]);
			$row_num++;
		}

		$body = new Google_Service_Sheets_BatchUpdateValuesRequest(array(
			'valueInputOption' => 'RAW',
			'data' => $data
		));

		$this->service->spreadsheets_values->batchUpdate($this->spreadsheet->spreadsheetId,$body);

		return $this;
	}

	/**
	 * サービスアカウント以外のユーザーにも権限を付与する（ブラウザから見たいため）
	 * 
	 * @param string $userGMailAddress gmailアドレス
	 * @return \Google_Service_Drive_Permission $response
	 */
	public function attatchAuthToUser($userGMailAddress)
	{
		// Google Drive ApisのPermission resourceを作成
		$permission = new Google_Service_Drive_Permission([
			'type'  => 'user',
			'role'  => 'writer',
			'emailAddress' => $userGMailAddress,
		]);

		// Permission resourceを割り当て
		$response = (new Google_Service_Drive($this->client))
			->permissions
			->create($this->spreadsheet->spreadsheetId,$permission);

		return $response;
	}
}