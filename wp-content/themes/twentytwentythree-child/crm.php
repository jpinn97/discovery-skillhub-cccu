<?php
class evoEnquiry
{

	const ROOT = 'NewDataSet';
	const ELEMENT = 'User';
	const HOST = 'http://discoverypark.evolutive.co.uk/services/wsForm.asmx?WSDL';

	private $passcode = '';
	private $soapParameters = array(
		'soap_version' => SOAP_1_2,
		'trace' => 1,
		'style' => SOAP_RPC,
		'use' => SOAP_ENCODED
	);

	public function __construct($passcode)
	{
		$this->passcode = $passcode;
	}

	public function addForm($data)
	{
		$response = $this->callSoapRequest(
			"AddFormXML",
			$this->getParameterList("sXML", $this->getXML($data))
		);
		var_dump($response);
	}

	private function callSoapRequest($method, $parameters)
	{
		if ($this->passcode != '' || $method) {
			$client = new SoapClient(evoEnquiry::HOST, $this->soapParameters);
			try {
				$result = $client->__soapCall($method, $parameters);
				$response = $client->__getLastResponse();
			} catch (SoapFault $fault) {
				$response = $fault->faultstring;
			}
			echo '<div style="font-family:tahoma;font-size:13px;margin-bottom:30px;">';
			echo '<span style="font-weight:bold;" >' . $method . '</span>';
			echo '<br /><br />';
			echo '<span style="font-weight:bold;" >Parameters - </span>';
			var_dump($parameters);
			echo '<br /><br />';
			echo '<span style="font-weight:bold;" >Response - </span>';
			var_dump($response);
			echo '</div>';
			return $response;
		}
	}

	private function getXML($data)
	{
		$xmlString = '<' . evoEnquiry::ROOT . '>';
		$xmlString .= $this->buildItem($data);
		$xmlString .= '</' . evoEnquiry::ROOT . '>';
		return $xmlString;
	}

	private function buildItem($data)
	{
		$xmlString = '<' . evoEnquiry::ELEMENT . '>';
		foreach ($data as $field => $value)
			$xmlString .= '<' . $field . '>' . $value . '</' . $field . '>';
		$xmlString .= '</' . evoEnquiry::ELEMENT . '>';
		return $xmlString;
	}
	private function getParameterList($key, $value)
	{
		return array("Parameters" => array("sApiKey" => getenv('EVO_API_KEY'), $key => $value));
	}
}

$enquiry = array(
	'sCompanyName' => 'Test Company',
	'sTitle' => 'Test Title',
	'sFirstName' => 'Test First Name',
	'sSurname' => 'Test Surname',
	'sAddressBuilding' => 'Test Building',
	'sAddressSecondaryName' => '',
	'sAddressStreet' => 'Test Road',
	'sAddressDistrict' => 'Test District',
	'sAddressTown' => 'Test Town',
	'sAddressCounty' => 'Test County',
	'sAddressPostcode' => 'S35 2PG',
	'sTelephone' => '0114 2573645',
	'sEmail' => 'Test@Test.com',
	'lEnquiryType' => '0',
	'lCategoryIDs' => '99,102,958',
	'sComments'		=> ''
);

$apiKey = getenv('EVO_API_KEY');

$cEnquiry = new evoEnquiry($apiKey);
$cEnquiry->addForm($enquiry);
