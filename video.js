
// Adapted from Clara Nees - https://github.com/cnees/video

function onYouTubeIframeAPIReady() {videoPlayer.createPlayer();}

function onPlayerReady(event) {videoPlayer.playerReady(event);}

function onPlayerStateChange(event) {videoPlayer.stateChange(event);}

function onPlaybackRateChange(event) {videoPlayer.onPlaybackRateChange(event);}

var videoViews = {
        interval: 1, // Number of seconds per bin
        binCount: 120, // Number of bins
        bins: null, // Array of bins
        updateInterval: null,
        updated_at: 0,
        counter: 0, // Number of times videoViews has been updated since last database update

        setUpdateInterval: function() {
                if(this.updateInterval === null) {
                        const rate = videoPlayer.player.getPlaybackRate();
                        console.log('Starting interval rate=', rate);
                        this.updateInterval = setInterval(function() {videoViews.updateViews();}, this.interval * (1000 / videoPlayer.player.getPlaybackRate()));
                }
        },

        getDuration: function() {
                var duration = videoPlayer.player.getDuration();
                if ( duration > 7200 ) duration = 7200;  // Only track first 2 hours
                return duration;
        },

        unsetUpdateInterval: function() {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
        },

        initialize: function() { // Don't initialize until the videoPlayer is ready
                var duration = this.getDuration();
                this.interval = Math.max(Math.ceil(duration / this.binCount), 1);
                this.binCount = Math.ceil(duration / this.interval);
                // Zero filled array of size binCount
                this.bins = Array.apply(null, new Array(this.binCount)).map(Number.prototype.valueOf,0);
                this.updated_at = (new Date().getTime())/ 1000;
                this.setUpdateInterval(this.interval);
        },

        updateViews: function () {
                console.debug('updateViews');
                var bin = Math.floor(videoPlayer.player.getCurrentTime() / this.interval); // Round down to nearest interval
                var now = (new Date().getTime())/ 1000;
                var delta = now - this.updated_at;
                console.log('bin '+bin+' now '+now+' delta '+delta);
                if ( bin > this.binCount ) return;
                this.bins[bin] += 1;
                this.counter++;
                if(delta > 10 || ( delta > 5 && this.counter > 10) ) {
                        this.sendToDB();
                }
        },
        sendToDB: function() {
                console.log('Send to DB');
                this.counter = 0;
                this.updated_at = (new Date().getTime())/ 1000;
                var message = {
                        state: videoPlayerState,
                        rate: videoPlayer.player.getPlaybackRate(),
                        duration: this.getDuration(),
                        interval: this.interval,
                        vector: this.bins
                };
                console.log(JSON.stringify(message));
                $.post(TRACKING_URL, message, function(data) {
                        console.debug(data);
                });
                // Reset the bins - don't wait.
                var i = this.bins.length - 1;
                while(i >= 0) this.bins[i--] = 0;
        }
}

// https://developers.google.com/youtube/player_parameters
// https://stackoverflow.com/questions/63792338/disable-cookies-when-using-the-youtube-iframe-player-api-script-with-the-youtube
var videoPlayerState = 'init';
var videoPlayer = {

    player: null,
    createPlayer: function() {
        this.player = new YT.Player('player', {
            host: 'https://www.youtube-nocookie.com',
            videoId: VIDEO_ID, // Defined in HTML
            playerVars: {
              'rel' : 0,
			  'enablejsapi' : 1,
			  'origin' : 'https://dj4e.com',
              'playsinline': 1
            },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange,
                'onPlaybackRateChange': onPlaybackRateChange
            }
        });
    },

    playerReady: function(event) {
        this.player.playVideo();
        videoViews.initialize();
        videoViews.setUpdateInterval();
    },

    loadAPI: function() {
        // This code loads the IFrame Player API code asynchronously.
        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    },

    onPlaybackRateChange: function(event) {
        console.log("Rate change", event);
        if ( videoPlayerState == 'playing' ) {
            videoViews.sendToDB();
            videoViews.unsetUpdateInterval();
            videoViews.setUpdateInterval();
        }
    },

    stateChange: function(event) {
        if (event.data == YT.PlayerState.PLAYING) {
            videoPlayerState = 'playing';
            console.log('Player playing');
            videoViews.setUpdateInterval();
        }
        else if (event.data == YT.PlayerState.PAUSED) {
            videoPlayerState = 'paused';
            console.log('Player paused');
            videoViews.unsetUpdateInterval();
            videoViews.sendToDB();
        } else if (event.data == YT.PlayerState.ENDED) {
            videoPlayerState = 'finished';
            console.log('Player finished');
            videoViews.sendToDB();
        }
    }

}

$(document).ready(function() {
    videoPlayer.loadAPI();
});
