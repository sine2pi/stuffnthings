<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Video180</title>
  <style>
    body {
      margin: 0;
      padding: 2% 8%;
      background-color: #f0f0f0;
      font-family: Arial, sans-serif;
    }

    .video-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center; 
    }

    .cover-image,
    video {
      width: 100%;
      height: 100%;
      border-radius: 1px;
      cursor: pointer;
      position: absolute;
    }

    .video-wrapper {
    width: 266px;
    height: 166px;
    background-color: white;
    padding: 3px;
    border-radius: 2px;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
    position: relative;
    /* Add padding-bottom to maintain aspect ratio */
    padding: 1.5%; /* Example: 150% for 2:3 aspect ratio */
    }

    .cover-image {
    z-index: 1;
    overflow: hidden;
    /* Position the image absolutely within the container */
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    }

    .video-details {
    font-size: 12px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    overflow: scroll;  /* or overflow: scroll; */
    height: 105%;
    width: 105%;
    position: relative;
    justify-content: flex-start;
    right: 0px;
    }

    .play-button {
      padding: 1px 2px;
      background-color: Linen;
      opacity: 90%;
      color: black;
      border: 0.5px;
      border-radius: 1px;
      cursor: pointer;
      z-index: 3;
      margin-bottom: 3px;
      /* Added a small margin */
    }

    .play-button:hover {
      background-color: #0056b3;
    }

    .video-filename {
      font-size: 12px;
      padding: 2px 2px;
      background-color: Linen;

      opacity: 90%;
      color: black;

      border-radius: 1px;
      position: absolute;
      bottom: 0px;
      left: 0;
      z-index: 3;
      cursor: pointer;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 100;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 80%;
      height: 80%;
      max-width: 900px;
      border-radius: 10px;
      box-sizing: border-box;
    }

    .modal-close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      position: relative;
      z-index: 5;
    }

    .modal-close:hover,
    .modal-close:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
    }

    #videoContent {
      height: inherit;
      position: relative;
      z-index: 1;
      top: 10%;
    }

    #videoContent video {
      width: 100%;
      height: 100%;
      max-height: 100%;
    }

    #searchBar {
      margin-bottom: 20px;
      padding: 10px;
      width: 100%;
      box-sizing: border-box;
      font-size: 16px;
    }

    @media (max-width: 960px) {
      .video-wrapper {
        width: calc(100% / 3 - 20px);
      }
    }
  </style>
</head>



</html>
<body>
  <?php

    $videoDir = './movies';
    $thumbnailDir = './thumbnails';
    $nfoDir = './nfo';


    if (!file_exists($thumbnailDir)) {
        mkdir($thumbnailDir, 0777, true);
    }
    if (!file_exists($nfoDir)) {
        mkdir($nfoDir, 0777, true);
    }

    $files = scandir($videoDir);
    $videos = array_filter($files, function($file) use ($videoDir) {
        return is_file("$videoDir/$file") && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'mp4';
    });

    // Function to parse NFO files and extract metadata
    function parseNFO($nfoFile) {
        $nfoData = simplexml_load_file($nfoFile);
        if ($nfoData === false) {
            return [];
        }

        $metadata = [
            'title' => (string)$nfoData->title,
            'director' => (string)$nfoData->director,
            'studio' => (string)$nfoData->studio,
            'year' => (string)$nfoData->year,
            'plot' => (string)$nfoData->plot,
            'runtime' => (string)$nfoData->runtime,
            'rating' => (string)$nfoData->rating,
            'genres' => [],
            'actors' => []
        ];

        foreach ($nfoData->genre as $genre) {
            $metadata['genres'][] = (string)$genre;
        }

        foreach ($nfoData->actor as $actor) {
            $metadata['actors'][] = (string)$actor->name;
        }

        return $metadata;
    }

    $groupedVideos = [];
    foreach ($videos as $video) {
        $baseName = preg_replace('/-[A-Z]$/', '', pathinfo($video, PATHINFO_FILENAME)); 
        $nfoFilePath = "$nfoDir/$baseName.nfo";
        $metadata = file_exists($nfoFilePath) ? parseNFO($nfoFilePath) : [];

        if (!isset($groupedVideos[$baseName])) {
            $groupedVideos[$baseName] = [
                'videos' => [],
                'metadata' => $metadata
            ];
        }

        $groupedVideos[$baseName]['videos'][] = $video; 
    }
  ?>

<input type="text" id="searchBar" placeholder="Search videos...">
  <div class="video-container" id="videoContainer">
    <?php foreach ($groupedVideos as $baseName => $data): ?>
    <div class="video-wrapper" data-filename="<?php echo htmlspecialchars($baseName); ?>"
      data-metadata="<?php echo htmlspecialchars(json_encode($data['metadata'])); ?>">
      <?php
                $coverImagePath = "$thumbnailDir/" . $baseName . "P.jpg";
                if (file_exists($coverImagePath)): ?>
      <img src="<?php echo $coverImagePath; ?>" alt="Cover Image" class="cover-image">
      <?php else: ?>
      <img src="thumbnails/placeholder.jpg" alt="Thumbnail" class="thumbnail">
      <?php endif; ?>
      <div class="video-details">
        <span class="video-filename"
          onclick="showMetadata('<?php echo htmlspecialchars($baseName); ?>')"><?php echo htmlspecialchars($baseName); ?></span>
        <?php foreach ($data['videos'] as $index => $videoPart): ?>
        <a href="<?php echo htmlspecialchars($videoDir . '/' . $videoPart); ?>" target="_blank" class="play-button">
          <?php echo htmlspecialchars('Part ' . ($index + 1)); ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div id="metadataModal" class="modal">
    <div class="modal-content">
      <span class="modal-close" onclick="closeModal()">&times;</span>
      <div id="metadataContent"></div>
    </div>
  </div>

  <div id="videoModal" class="modal">
    <div class="modal-content">
      <span class="modal-close" onclick="closeVideoModal()">&times;</span>
      <div id="videoContent"></div>
    </div>
  </div>
  
  <script>
    function playVideo(videoFile) {
      const videoElement = document.getElementById(`video-${videoFile}`);
      const videoModal = document.getElementById('videoModal');
      const videoContent = document.getElementById('videoContent');

      videoContent.innerHTML = `
                <video 
                    src="${videoElement.dataset.src}" 
                    controls 
                    autoplay 
                    style="width: 100%; height: 100%;">
                </video>`;
      videoModal.style.display = 'block';
    }

    function closeVideoModal() {
      const videoModal = document.getElementById('videoModal');
      const videoContent = document.getElementById('videoContent');

      videoModal.style.display = 'none';
      videoContent.innerHTML = ''; 
    }

    function showMetadata(baseName) {
      const metadataElement = document.getElementById(`metadata-${baseName}`);
      const metadataModal = document.getElementById('metadataModal');
      const metadataContent = document.getElementById('metadataContent');

      const metadata = JSON.parse(document.querySelector(`[data-filename="${baseName}"]`).getAttribute('data-metadata'));
      metadataContent.innerHTML = `
            <h3>${metadata.title || ''}</h3>
            <p><strong>Director:</strong> ${metadata.director || ''}</p>
            <p><strong>Studio:</strong> ${metadata.studio || ''}</p>
            <p><strong>Year:</strong> ${metadata.year || ''}</p>
            <p><strong>Plot:</strong> ${metadata.plot || ''}</p>
            <p><strong>Runtime:</strong> ${metadata.runtime || ''} minutes</p>
            <p><strong>Rating:</strong> ${metadata.rating || ''}/5</p>
            <p><strong>Genres:</strong> ${metadata.genres ? metadata.genres.join(', ') : ''}</p>
            <p><strong>Actors:</strong> ${metadata.actors ? metadata.actors.join(', ') : ''}</p>
        `;
      metadataModal.style.display = 'block';
    }

    function closeModal() {
      document.getElementById('metadataModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const videoModal = document.getElementById('videoModal');
      const metadataModal = document.getElementById('metadataModal');
      if (event.target == videoModal) {
        closeVideoModal();
      } else if (event.target == metadataModal) {
        metadataModal.style.display = 'none';
      }
    }
  </script>

  <script>
    function openAllParts(baseName) {
      const videoParts = JSON.parse(document.querySelector(`[data-filename="${baseName}"]`).getAttribute('data-videos'));

      videoParts.forEach(part => {
        window.open(part, '_blank');
      });
    }
  </script>

  <script>

    document.getElementById('searchBar').addEventListener('input', function() {
      const searchQuery = this.value.toLowerCase();
      const videos = document.querySelectorAll('.video-wrapper');
      videos.forEach(function(video) {
        const filename = video.getAttribute('data-filename').toLowerCase();
        const metadata = JSON.parse(video.getAttribute('data-metadata').toLowerCase());

        let match = false;
        for (const key in metadata) {
          if (Array.isArray(metadata[key])) {
            metadata[key].forEach(item => {
              if (item.toLowerCase().includes(searchQuery)) {
                match = true;
              }
            });
          } else if (typeof metadata[key] === 'string') {
            if (metadata[key].toLowerCase().includes(searchQuery)) {
              match = true;
            }
          }
        }

        if (filename.includes(searchQuery) || match) {
          video.style.display = 'block';
        } else {
          video.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>
