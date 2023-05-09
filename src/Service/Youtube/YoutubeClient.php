<?php

declare(strict_types=1);

namespace PlaylistCreator\Service\Youtube;

use PlaylistCreator\Exception\YoutubeException;
use PlaylistCreator\Interface\YoutubeClientInterface;
use PlaylistCreator\Model\SearchResult;

final class YoutubeClient implements YoutubeClientInterface
{
    private YoutubeServiceProvider $provider;

    public function __construct()
    {
        $this->provider = new YoutubeServiceProvider();
    }

    /**
     * @throws \PlaylistCreator\Exception\YoutubeException
     */
    public function searchVideoId(string $searchText): ?SearchResult
    {
        try {
            $searchService = $this->provider->getSearchService();
            $searchResponse = $searchService->listSearch('id,snippet', [
                'q' => $searchText,
                'maxResults' => 1,
                'type' => 'video',
            ]);

            if (0 === count($searchResponse->getItems())) {
                return null;
            }

            return new SearchResult(
                html_entity_decode($searchResponse->getItems()[0]->getSnippet()->getTitle()),
                $searchResponse->getItems()[0]->getId()->getVideoId()
            );
        } catch (\Exception $e) {
            throw new YoutubeException(message: 'Error searching for song on Youtube', previous: $e);
        }
    }

    /**
     * @throws \PlaylistCreator\Exception\YoutubeException
     */
    public function insertInPlaylist(string $videoId, string $playlistId): void
    {
        try {
            $playlistItem = new \Google_Service_YouTube_PlaylistItem();
            $playlistItemSnippet = new \Google_Service_YouTube_PlaylistItemSnippet();
            $playlistItemSnippet->setPlaylistId($playlistId);
            $playlistItemSnippet->setPosition(0);
            $resourceId = new \Google_Service_YouTube_ResourceId();
            $resourceId->setKind('youtube#video');
            $resourceId->setVideoId($videoId);
            $playlistItemSnippet->setResourceId($resourceId);
            $playlistItem->setSnippet($playlistItemSnippet);
            $playlistItemService = $this->provider->getPlaylistItemsService();
            $playlistItemService->insert('snippet', $playlistItem);
        } catch (\Exception $e) {
            throw new YoutubeException(message: 'Error adding song to playlist', previous: $e);
        }
    }

    /**
     * @throws \PlaylistCreator\Exception\YoutubeException
     */
    public function addPlaylist(string $title): string
    {
        try {
            $playlist = new \Google_Service_YouTube_Playlist();
            $playlistSnippet = new \Google_Service_YouTube_PlaylistSnippet();
            $playlistSnippet->setTitle($title);
            $playlist->setSnippet($playlistSnippet);
            $playlistsService = $this->provider->getPlaylistService();
            $response = $playlistsService->insert('id,snippet', $playlist);

            return $response->getId();
        } catch (\Exception $e) {
            throw new YoutubeException(message: 'Error adding playlist "'.$title.'"', previous: $e);
        }
    }

    /**
     * @throws \PlaylistCreator\Exception\YoutubeException
     */
    public function hasPlaylist(string $id): bool
    {
        try {
            $playlistsService = $this->provider->getPlaylistService();
            $queryParams = [
                'mine' => true,
            ];
            $response = $playlistsService->listPlaylists('id, snippet', $queryParams);
        } catch (\Exception $e) {
            throw new YoutubeException('Error retrieving playlist with ID "'.$id.'"', 0, $e);
        }

        $playlists = $response->getItems();

        return 0 !== count($playlists);
    }
}
