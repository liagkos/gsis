<?php

/**
 * Class AFMinfo
 *
 * GSIS SOAP REQUEST Class for AFM info
 *
 * Copyright 2011-2017 Athanasios Liagkos - me@nasos.work
 * https://github.com/liagkos/gsis
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * @version 2.0
 *
 */
Class AFMinfo
{
    /**
     * Client object
     *
     * @var SoapClient
     */
    private $objClient;
    /**
     * GSIS WSDL URL
     *
     * @var string
     */
    private $WSDL      = 'https://www1.gsis.gr/webtax2/wsgsis/RgWsPublic/RgWsPublicPort?WSDL';
    /**
     * GSIS Endpoint URL
     *
     * @var string
     */
    private $location  = 'https://www1.gsis.gr/webtax2/wsgsis/RgWsPublic/RgWsPublicPort';
    /**
     * Namespace
     *
     * @var string
     */
    private $strWSSENS = 'http://schemas.xmlsoap.org/ws/2002/07/secext';

    /**
     * AFMinfo constructor
     *
     * Get credentials from https://www1.gsis.gr/sgsisapps/tokenservices/protected/displayConsole.htm
     *
     * @param string $username GSIS Username from
     * @param string $password GSIS Password
     *
     * @return bool true on success
     *
     * @from 1.0
     */
    public function __construct($username, $password)
    {
	
		// Code copied and modified from http://www.php.net/manual/en/soapclient.soapclient.php#97273
	
		$objSoapVarUser = new SoapVar($username, XSD_STRING, NULL, $this->strWSSENS, NULL, $this->strWSSENS);
		$objSoapVarPass = new SoapVar($password, XSD_STRING, NULL, $this->strWSSENS, NULL, $this->strWSSENS);
		
		$objWSSEAuth = (object) array('Username' => $objSoapVarUser,  'Password' => $objSoapVarPass);
		$objSoapVarWSSEAuth = new SoapVar($objWSSEAuth, SOAP_ENC_OBJECT, NULL, $this->strWSSENS, 'UsernameToken', $this->strWSSENS);

		$objWSSEToken = (object) array('UsernameToken' => $objSoapVarWSSEAuth);
		$objSoapVarWSSEToken = new SoapVar($objWSSEToken, SOAP_ENC_OBJECT, NULL, $this->strWSSENS, 'UsernameToken', $this->strWSSENS);

		$objSoapVarHeaderVal=new SoapVar($objSoapVarWSSEToken, SOAP_ENC_OBJECT, NULL, $this->strWSSENS, 'Security', $this->strWSSENS);
		$objSoapVarWSSEHeader = new SoapHeader($this->strWSSENS, 'Security', $objSoapVarHeaderVal);

		try {
            $this->objClient = new SoapClient($this->WSDL, array('trace' => 0));
        } catch (SoapFault $e) {
		    // SOAP error
		    return false;
        }

		$this->objClient->__setLocation($this->location);
		$this->objClient->__setSoapHeaders(array($objSoapVarWSSEHeader));

		return true;
	}

    /**
     * Execution of query
     *
     * @param string $afmFrom   VAT ID of caller
     * @param string $afmFor    VAT ID to look for
     * @param string $strMethod Method default = rgWsPublicAfmMethod
     *
     * @return array|bool       Array of results or false for SOAP error
     *
     * @from 1.0
     */
    public function exec($afmFrom = '', $afmFor = '', $strMethod = 'rgWsPublicAfmMethod')
	{
		if ($strMethod == 'rgWsPublicAfmMethod') {

		    try {
                $response = $this->objClient->$strMethod(array('afmCalledBy' => $afmFrom, 'afmCalledFor' => $afmFor));
            } catch (SoapFault $e) {
		        // SOAP error
		        return false;
            }

			$response = $this->formatResult($response);
            $response['query'] = $afmFor;

		} else {

		    try {
                $response = $this->objClient->$strMethod();
            } catch (SoapFault $e) {
		        // SOAP error
		        return false;
            }

		}

		return $response;
	}

    /**
     * Convert dates to greek format
     * 2014-05-07T00:00:00.000+02:00 to Wed, 07 May 2014
     *
     * @param string $dateStr Raw date from GSIS
     *
     * @return string Formatted date
     *
     * @from 1.0
     */
    private function formatDate($dateStr) { // Convert
        $patterns = array(
            '/Mon/', '/Tue/', '/Wed/', '/Thu/', '/Fri/', '/Sat/', '/Sun/',
            '/Jan/', '/Feb/', '/Mar/', '/Apr/', '/May/', '/Jun/', '/Jul/', '/Sep/', '/Oct/', '/Nov/', '/Dec/'
        );
        $replacements = array(
            'Δευτέρα', 'Τρίτη', 'Τετάρτη', 'Πέμπτη', 'Παρασκευή', 'Σάββατο', 'Κυριακή',
            'Ιανουαρίου', 'Φεβρουαρίου', 'Μαρτίου', 'Απριλίου', 'Μαΐου', 'Ιουνίου',
            'Ιουλίου', 'Αυγούστου', 'Σεπτεμβρίου', 'Οκτωβρίου', 'Νοεμβρίου', 'Δεκεμβρίου'
        );
        $dateEnglish = date('D, d M Y', strtotime($dateStr));

        return preg_replace($patterns, $replacements, $dateEnglish);
	}

    /**
     * Format result as array
     *
     * @param ArrayObject $resultObj
     * @return array
     *
     * @from 1.0
     */
    private function formatResult($resultObj)
	{
		$result = array();
		
        $result['success']    = $resultObj['pErrorRec_out']->errorCode == NULL;
        $result['errorCode']  = trim($resultObj['pErrorRec_out']->errorCode);
        $result['errorDescr'] = trim($resultObj['pErrorRec_out']->errorDescr);
        $result['requestID']  = trim($resultObj['pCallSeqId_out']);
		
		if (array_key_exists('RgWsPublicBasicRt_out', $resultObj)) {
            // ΑΦΜ
            $result['afm']             = trim($resultObj['RgWsPublicBasicRt_out']->afm);
            // Κωδικός ΔΟΥ
            $result['doy']             = trim($resultObj['RgWsPublicBasicRt_out']->doy);
            // Περιγραφή ΔΟΥ
            $result['doyDescr']        = trim($resultObj['RgWsPublicBasicRt_out']->doyDescr);
            // 1 = Ενεργός ΑΦΜ, 2 = Απενερφοποιημένος ΑΦΜ
            $result['afmStatus']       = trim($resultObj['RgWsPublicBasicRt_out']->deactivationFlag);
            // ΕΝΕΡΓΟΣ ΑΦΜ, ΑΠΕΝΕΡΓΟΠΟΙΗΜΕΝΟΣ ΑΦΜ
            $result['afmStatusDescr']  = trim($resultObj['RgWsPublicBasicRt_out']->deactivationFlagDescr);
            // ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ, ΜΗ ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ, ΠΡΩΗΝ ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ
            $result['afmType']         = trim($resultObj['RgWsPublicBasicRt_out']->firmFlagDescr);
            // ΦΠ, ΜΗ ΦΠ
            $result['personType']      = trim($resultObj['RgWsPublicBasicRt_out']->INiFlagDescr);
            // Περιγραφή μορφής μη φυσικού προσώπου
            $result['legalPersonType'] = trim($resultObj['RgWsPublicBasicRt_out']->legalStatusDescr);
            // Επωνυμία
            $result['fullName']        = trim($resultObj['RgWsPublicBasicRt_out']->onomasia);
            // Διακριτικός τίτλος επιχείρησης
            $result['commercialTitle'] = trim($resultObj['RgWsPublicBasicRt_out']->commerTitle);
            // Οδός επιχείρησης
            $result['streetName']      = trim($resultObj['RgWsPublicBasicRt_out']->postalAddress);
            // Αριθμός επιχείρησης
            $result['streetNo']        = trim($resultObj['RgWsPublicBasicRt_out']->postalAddressNo);
            // Οδός & αριθμός επιχείρησης
            $result['street']          = $result['streetName'].' '.$result['streetNo'];
            // Ταχυδρομικός κώδικας επιχείρησης
            $result['zip']             = trim($resultObj['RgWsPublicBasicRt_out']->postalZipCode);
            // Πόλη επιχείρησης
            $result['city']            = trim($resultObj['RgWsPublicBasicRt_out']->postalAreaDescription);
            // Πλήρης διεύθυνση επιχείρησης
            $result['addressFull']     = $result['street'].', '.$result['city'].' '.$result['zip'];
            if ($result['city'] == NULL) unset($result['addressFull']);
            // Ημ/νία έναρξης 2014-05-07T00:00:00.000+02:00
            $result['startDateRaw']    = trim($resultObj['RgWsPublicBasicRt_out']->registDate);
            // Ημ/νία έναρξης Wed, 07 May 2014
            $result['startDate']       = $result['startDateRaw'] ? $this->formatDate($result['startDateRaw']) : false;
            // Ημ/νία διακοπής 2014-05-07T00:00:00.000+02:00
            $result['stopDateRaw']     = trim($resultObj['RgWsPublicBasicRt_out']->stopDate);
            // Ημ/νία διακοπής Wed, 07 May 2014
            $result['stopDate']        = $result['stopDateRaw'] ? $this->formatDate($result['stopDateRaw']) : false;
		}
		
		if (property_exists($resultObj['arrayOfRgWsPublicFirmActRt_out'], 'RgWsPublicFirmActRtUser')) {
		
			$result['kad'] = array();
		
			$kad1 = $kad2 = $kad3 = $kad4 = array();

            // Αν υπάρχει μόνο κύρια, το RgWsPublicFirmActRtUser δεν είναι array
			if (!is_array($resultObj['arrayOfRgWsPublicFirmActRt_out']->RgWsPublicFirmActRtUser))
				$resultObj['arrayOfRgWsPublicFirmActRt_out']->RgWsPublicFirmActRtUser = array(
				    $resultObj['arrayOfRgWsPublicFirmActRt_out']->RgWsPublicFirmActRtUser
                );
			
			foreach ($resultObj['arrayOfRgWsPublicFirmActRt_out']->RgWsPublicFirmActRtUser as $k=>$v) {
				$data = array($v->firmActCode, $v->firmActKind, trim($v->firmActKindDescr), trim($v->firmActDescr));
				${'kad' . $v->firmActKind}[(int) $v->firmActCode] = $data;
			}
			
			sort($kad1);
			sort($kad2);
			sort($kad3);
			sort($kad4);
			
			if (count($kad1)) {
				foreach ($kad1 as $v) {
					$result['kad']['base'][$v[0]] = $v[3];
				}
			}
			
			if (count($kad2)) {
				foreach ($kad2 as $v) {
					$result['kad']['secondary'][$v[0]] = $v[3];
				}
			}
			
			if (count($kad3)) {
				foreach ($kad3 as $v) {
					$result['kad']['misc'][$v[0]] = $v[3];
				}
			}
			
			if (count($kad4)) {
				foreach ($kad4 as $v) {
					$result['kad']['suppl'][$v[0]] = $v[3];
				}
			}
			
		}
		
		return $result;
	}
}
