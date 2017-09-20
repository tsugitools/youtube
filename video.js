
// Adapted from Clara Nees - https://github.com/cnees/video

function onYouTubeIframeAPIReady() {videoPlayer.createPlayer();}

function onPlayerReady(event) {videoPlayer.playerReady(event);}

function onPlayerStateChange(event) {videoPlayer.stateChange(event);}

var videoViews = {
        interval: 1, // Number of seconds per bin
        binCount: 120, // Number of bins
        bins: null, // Array of bins
        updateInterval: null,
        updated_at: 0,
        counter: 0, // Number of times videoViews has been updated since last database update

        setUpdateInterval: function() {
                if(this.updateInterval === null) {
                        this.updateInterval = setInterval(function() {videoViews.updateViews();}, this.interval * 1000);
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
                var bin = Math.floor(videoPlayer.player.getCurrentTime() / this.interval); // Round down to nearest interval
                var now = (new Date().getTime())/ 1000;
                var delta = now - this.updated_at;
                console.log('bin '+bin+' now '+now+' delta '+delta);
                if ( bin > this.binCount ) return;
                this.bins[bin] += 1;
                this.counter++;
                // TODO: Fix
                if(true || delta > 30 || ( delta > 10 && this.counter > 10) ) {
                        this.sendToDB();
                }
        },
        sendToDB: function() {
                this.counter = 0;
                this.updated_at = (new Date().getTime())/ 1000;
                var message = {
                        duration: this.getDuration(),
                        interval: this.interval,
                        vector: this.bins
                };
                console.log(JSON.stringify(message));
                $.post(TRACKING_URL, message, function(data) {
                        //console.log(data);
                });
                // Reset the bins - don't wait.
                var i = this.bins.length - 1;
                while(i >= 0) this.bins[i--] = 0;
        }
}

var videoPlayer = {

    player: null,
    createPlayer: function() {
        this.player = new YT.Player('player', {
            videoId: VIDEO_ID, // Defined in HTML
            playerVars: {rel:0},
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
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

    stateChange: function(event) {
        if (event.data == YT.PlayerState.PLAYING) {
            videoViews.setUpdateInterval();

        }
        else { // Not playing
            videoViews.unsetUpdateInterval();
        }
        if(event.data == YT.PlayerState.ENDED) {
            videoViews.sendToDB();
        }
    }

}

$(document).ready(function() {
    videoPlayer.loadAPI();
});
