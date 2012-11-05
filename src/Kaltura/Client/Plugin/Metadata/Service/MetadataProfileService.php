<?php
// ===================================================================================================
//                           _  __     _ _
//                          | |/ /__ _| | |_ _  _ _ _ __ _
//                          | ' </ _` | |  _| || | '_/ _` |
//                          |_|\_\__,_|_|\__|\_,_|_| \__,_|
//
// This file is part of the Kaltura Collaborative Media Suite which allows users
// to do with audio, video, and animation what Wiki platfroms allow them to do with
// text.
//
// Copyright (C) 2006-2011  Kaltura Inc.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// @ignore
// ===================================================================================================


/**
 * @namespace
 */
namespace Kaltura\Client\Plugin\Metadata\Service;

/**
 * Metadata Profile service
 *  
 * @package Kaltura
 * @subpackage Client
 */
class MetadataProfileService extends \Kaltura\Client\ServiceBase
{
	function __construct(\Kaltura\Client\Client $client = null)
	{
		parent::__construct($client);
	}

	/**
	 * Allows you to add a metadata profile object and metadata profile content associated with Kaltura object type
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile
	 */
	function add(\Kaltura\Client\Plugin\Metadata\Type\MetadataProfile $metadataProfile, $xsdData, $viewsData = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "metadataProfile", $metadataProfile->toParams());
		$this->client->addParam($kparams, "xsdData", $xsdData);
		$this->client->addParam($kparams, "viewsData", $viewsData);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "add", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfile");
		return $resultObject;
	}

	/**
	 * Allows you to add a metadata profile object and metadata profile file associated with Kaltura object type
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile
	 */
	function addFromFile(\Kaltura\Client\Plugin\Metadata\Type\MetadataProfile $metadataProfile, $xsdFile, $viewsFile = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "metadataProfile", $metadataProfile->toParams());
		$kfiles = array();
		$this->client->addParam($kfiles, "xsdFile", $xsdFile);
		$this->client->addParam($kfiles, "viewsFile", $viewsFile);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "addFromFile", $kparams, $kfiles);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfile");
		return $resultObject;
	}

	/**
	 * Retrieve a metadata profile object by id
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile
	 */
	function get($id)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "get", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfile");
		return $resultObject;
	}

	/**
	 * Update an existing metadata object
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile
	 */
	function update($id, \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile $metadataProfile, $xsdData = null, $viewsData = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "metadataProfile", $metadataProfile->toParams());
		$this->client->addParam($kparams, "xsdData", $xsdData);
		$this->client->addParam($kparams, "viewsData", $viewsData);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "update", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfile");
		return $resultObject;
	}

	/**
	 * List metadata profile objects by filter and pager
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfileListResponse
	 */
	function listAction(\Kaltura\Client\Plugin\Metadata\Type\MetadataProfileFilter $filter = null, \Kaltura\Client\Type\FilterPager $pager = null)
	{
		$kparams = array();
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		if ($pager !== null)
			$this->client->addParam($kparams, "pager", $pager->toParams());
		$this->client->queueServiceActionCall("metadata_metadataprofile", "list", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfileListResponse");
		return $resultObject;
	}

	/**
	 * List metadata profile fields by metadata profile id
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfileFieldListResponse
	 */
	function listFields($metadataProfileId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "metadataProfileId", $metadataProfileId);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "listFields", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfileFieldListResponse");
		return $resultObject;
	}

	/**
	 * Delete an existing metadata profile
	 * 	 
	 * 
	 * @return
	 */
	function delete($id)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "delete", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * Update an existing metadata object definition file
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile
	 */
	function revert($id, $toVersion)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "toVersion", $toVersion);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "revert", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfile");
		return $resultObject;
	}

	/**
	 * Update an existing metadata object definition file
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile
	 */
	function updateDefinitionFromFile($id, $xsdFile)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$kfiles = array();
		$this->client->addParam($kfiles, "xsdFile", $xsdFile);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "updateDefinitionFromFile", $kparams, $kfiles);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfile");
		return $resultObject;
	}

	/**
	 * Update an existing metadata object views file
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile
	 */
	function updateViewsFromFile($id, $viewsFile)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$kfiles = array();
		$this->client->addParam($kfiles, "viewsFile", $viewsFile);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "updateViewsFromFile", $kparams, $kfiles);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfile");
		return $resultObject;
	}

	/**
	 * Update an existing metadata object xslt file
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Metadata\Type\MetadataProfile
	 */
	function updateTransformationFromFile($id, $xsltFile)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$kfiles = array();
		$this->client->addParam($kfiles, "xsltFile", $xsltFile);
		$this->client->queueServiceActionCall("metadata_metadataprofile", "updateTransformationFromFile", $kparams, $kfiles);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Metadata\\Type\\MetadataProfile");
		return $resultObject;
	}

	/**
	 * Serves metadata profile XSD file
	 * 	 
	 * 
	 * @return file
	 */
	function serve($id)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->queueServiceActionCall('metadata_metadataprofile', 'serve', $kparams);
		$resultObject = $this->client->getServeUrl();
		return $resultObject;
	}

	/**
	 * Serves metadata profile view file
	 * 	 
	 * 
	 * @return file
	 */
	function serveView($id)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->queueServiceActionCall('metadata_metadataprofile', 'serveView', $kparams);
		$resultObject = $this->client->getServeUrl();
		return $resultObject;
	}
}
