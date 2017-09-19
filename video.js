
// Adapted from Clara Nees - https://github.com/cnees/video

function onYouTubeIframeAPIReady() {videoPlayer.createPlayer();}

function onPlayerReady(event) {videoPlayer.playerReady(event);}

function onPlayerStateChange(event) {videoPlayer.stateChange(event);}

var videoViews = {
        interval: 1, // Number of seconds per bin
        binCount: 100, // Number of bins
        bins: null, // Array of bins
        updateInterval: null,
        counter: 0, // Number of times videoViews has been updated since last database update
        setUpdateInterval: function() {
                if(this.updateInterval === null) {
                        this.updateInterval = setInterval(function() {videoViews.updateViews();}, this.interval * 1000);
                        console.log('Interval seconds: '+this.interval);
                }
        },


        unsetUpdateInterval: function() {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
        },
        initialize: function() { // Don't initialize until the videoPlayer is ready
                var duration = videoPlayer.player.getDuration();
                this.interval = Math.max(Math.ceil(duration / 100), 1);
                this.binCount = Math.ceil(duration / this.interval);
                this.bins = Array.apply(null, new Array(this.binCount)).map(Number.prototype.valueOf,0) // Zero filled array of size binCount
                this.setUpdateInterval(this.interval);
        },
        updateViews: function () {
                var bin = Math.floor(videoPlayer.player.getCurrentTime() / this.interval); // Round down to nearest interval
                this.bins[bin] += 1;
                this.counter++;
                // console.log(this.bins);
                // console.log("Counter: " + this.counter);
                if(this.counter === 5) {
                        this.sendToDB();
                }
        },
        sendToDB: function() {
                this.counter = 0;
                var message = {
                        vector: this.bins.toString()
                };
                console.log(this.bins);
/*
                $.post(VIEWSCALL, message, function(data) {
                        //console.log(data);
                });
                var i = this.bins.length - 1;
                while(i >= 0) this.bins[i--] = 0;
                //this.bins.map(function(){return 0;}); // Reset views to zero after adding them to the database
                var message = {
                        video_id: VIDEO_ID
                };
                videoChart.drawChart();
*/
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
	},

	playTime: function(cut, duration) {
		console.log(cut);
		console.log(duration);
		videoPlayer.player.playVideo();
		videoPlayer.player.seekTo(cut[0]);
		setTimeout(
			function() {videoPlayer.playTimes(cut, duration, 0);},
			duration[0]*1000
		);
	},

	playTimes: function(cut, duration, i) {
		console.log("Called playTimes");
		// Don't access this function directly. Use videoPlayer.playTime instead.
		var offset = cut[i] + duration[i] - videoPlayer.player.getCurrentTime();
		var waitForEndTime = function(){
		    clearInterval(waitInterval);
		    offset = cut[i] + duration[i] - videoPlayer.player.getCurrentTime();
		    console.log("Offset: " + offset);
		    if(offset > 10) {
		    	waitInterval = setInterval(waitForEndTime, offset);
		    }
		    else {
		    	console.log("Finished with offset " + offset);
		    }
		}
		var waitInterval = setInterval(waitForEndTime, offset);
		/*if(offset > 0) { // Check that player has reached end of interval
			console.log("Delaying");
			setTimeout( // Delay until end of interval
				function() {videoPlayer.playTimes(cut, duration, i);},
				offset*1000
			);
		}
		else */if(i < cut.length) {
			// Start next interval
			console.log("Starting new interval");
			++i;
			if(duration[i] === -1) {
				videoPlayer.player.pauseVideo();
			}
			else {
				videoPlayer.player.seekTo(cut[i]);
				setTimeout(
					function() {videoPlayer.playTimes(cut, duration, i);},
					duration[i]*1000
				);
			}
		}
		else {
			console.log("Pausing");
			videoPlayer.player.pauseVideo();
		}
	}
}
		
$(document).ready(function() {
	videoPlayer.loadAPI();
});
