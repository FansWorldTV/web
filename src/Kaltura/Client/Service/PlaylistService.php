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
namespace Kaltura\Client\Service;

/**
 * Playlist service lets you create,manage and play your playlists
 *  Playlists could be static (containing a fixed list of entries) or dynamic (baseed on a filter)
 *  
 * @package Kaltura
 * @subpackage Client
 */
class PlaylistService extends \Kaltura\Client\ServiceBase
{
	function __construct(\Kaltura\Client\Client $client = null)
	{
		parent::__construct($client);
	}

	/**
	 * Add new playlist
	 * 	 Note that all entries used in a playlist will become public and may appear in KalturaNetwork
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\Playlist
	 */
	function add(\Kaltura\Client\Type\Playlist $playlist, $updateStats = false)
	{
		$kparams = array();
		$this->client->addParam($kparams, "playlist", $playlist->toParams());
		$this->client->addParam($kparams, "updateStats", $updateStats);
		$this->client->queueServiceActionCall("playlist", "add", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\Playlist");
		return $resultObject;
	}

	/**
	 * Retrieve a playlist
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\Playlist
	 */
	function get($id, $version = -1)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "version", $version);
		$this->client->queueServiceActionCall("playlist", "get", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\Playlist");
		return $resultObject;
	}

	/**
	 * Update existing playlist
	 * 	 Note - you cannot change playlist type. updated playlist must be of the same type.
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\Playlist
	 */
	function update($id, \Kaltura\Client\Type\Playlist $playlist, $updateStats = false)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "playlist", $playlist->toParams());
		$this->client->addParam($kparams, "updateStats", $updateStats);
		$this->client->queueServiceActionCall("playlist", "update", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\Playlist");
		return $resultObject;
	}

	/**
	 * Delete existing playlist
	 * 	 
	 * 
	 * @return
	 */
	function delete($id)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->queueServiceActionCall("playlist", "delete", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * Clone an existing playlist
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\Playlist
	 */
	function cloneAction($id, \Kaltura\Client\Type\Playlist $newPlaylist = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		if ($newPlaylist !== null)
			$this->client->addParam($kparams, "newPlaylist", $newPlaylist->toParams());
		$this->client->queueServiceActionCall("playlist", "clone", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\Playlist");
		return $resultObject;
	}

	/**
	 * List available playlists
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\PlaylistListResponse
	 */
	function listAction(\Kaltura\Client\Type\PlaylistFilter $filter = null, \Kaltura\Client\Type\FilterPager $pager = null)
	{
		$kparams = array();
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		if ($pager !== null)
			$this->client->addParam($kparams, "pager", $pager->toParams());
		$this->client->queueServiceActionCall("playlist", "list", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\PlaylistListResponse");
		return $resultObject;
	}

	/**
	 * Retrieve playlist for playing purpose
	 * 	 
	 * 
	 * @return array
	 */
	function execute($id, $detailed = "")
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "detailed", $detailed);
		$this->client->queueServiceActionCall("playlist", "execute", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		if(!$resultObject)
			$resultObject = array();
		$this->client->validateObjectType($resultObject, "array");
		return $resultObject;
	}

	/**
	 * Retrieve playlist for playing purpose, based on content
	 * 	 
	 * 
	 * @return array
	 */
	function executeFromContent($playlistType, $playlistContent, $detailed = "")
	{
		$kparams = array();
		$this->client->addParam($kparams, "playlistType", $playlistType);
		$this->client->addParam($kparams, "playlistContent", $playlistContent);
		$this->client->addParam($kparams, "detailed", $detailed);
		$this->client->queueServiceActionCall("playlist", "executeFromContent", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		if(!$resultObject)
			$resultObject = array();
		$this->client->validateObjectType($resultObject, "array");
		return $resultObject;
	}

	/**
	 * Revrieve playlist for playing purpose, based on media entry filters
	 * 	 
	 * 
	 * @return array
	 */
	function executeFromFilters(array $filters, $totalResults, $detailed = "")
	{
		$kparams = array();
		foreach($filters as $index => $obj)
		{
			$this->client->addParam($kparams, "filters:$index", $obj->toParams());
		}
		$this->client->addParam($kparams, "totalResults", $totalResults);
		$this->client->addParam($kparams, "detailed", $detailed);
		$this->client->queueServiceActionCall("playlist", "executeFromFilters", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		if(!$resultObject)
			$resultObject = array();
		$this->client->validateObjectType($resultObject, "array");
		return $resultObject;
	}

	/**
	 * Retrieve playlist statistics
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\Playlist
	 */
	function getStatsFromContent($playlistType, $playlistContent)
	{
		$kparams = array();
		$this->client->addParam($kparams, "playlistType", $playlistType);
		$this->client->addParam($kparams, "playlistContent", $playlistContent);
		$this->client->queueServiceActionCall("playlist", "getStatsFromContent", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\Playlist");
		return $resultObject;
	}
}
