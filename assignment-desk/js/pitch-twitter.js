jQuery(document).ready(function() {
    // Declare variables to hold twitter API url and user name
    var twitter_api_url = 'http://search.twitter.com/search.json';
    var twitter_hash    = jQuery('#twitter-hash').html();

    // Enable caching
    jQuery.ajaxSetup({ cache: true });

    // Send JSON request
    // The returned JSON object will have a property called "results" where we find
    // a list of the tweets matching our request query
    jQuery.getJSON(
        twitter_api_url + '?callback=?&rpp=10&q=' + escape(twitter_hash),
        
        function(data) {
            
            var tweet_html = "";
                        
            jQuery.each(data.results, function(i, tweet) {
                // Uncomment line below to show tweet data in Fire Bug console
                // Very helpful to find out what is available in the tweet objects
                //console.log(tweet);

                // Before we continue we check that we got data
                if(tweet.text !== undefined) {
                    // Calculate how many hours ago was the tweet posted
                    var date_tweet = new Date(tweet.created_at);
                    var date_now   = new Date();
                    var date_diff  = date_now - date_tweet;
                    var hours      = Math.round(date_diff/(1000*60*60));
                    
                    var tweet_text = tweet.text;
                    
                    var template_fill = '<tr class="%%TWEET_TR_CLASS%%"> \
                                            <td>From Twitter</td> \
                                            <td>%%TWEET_TEXT%%</td> \
                                            <td>From Twitter</td> \
                                            <td>%%TWEET_TIME%%</td> \
                                            <td> \
                                                <form method="GET" action="admin.php"> \
                                                    <input name="page" value="assignment_desk-pitch" type="hidden">  \
                                                    <input name="action" value="detail" type="hidden"> \
                                                    <input name="summary" value="%%TWEET_TEXT%%" type="hidden"> \
                                                    <button class="button-secondary" type="submit">Import as Pitch</button> \
                                                </form> \
                                            </td> \
                                        </tr>';

                    // Build the html string for the current tweet
                    if(i%2 == 1){
                        template_fill = template_fill.replace("%%TWEET_TR_CLASS%%", "alternate");
                    }
                    // Fill in the text
                    template_fill = template_fill.replace("%%TWEET_TEXT%%", tweet_text);
                    template_fill = template_fill.replace("%%TWEET_TEXT%%", tweet_text);
                    // Full in the time
                    template_fill = template_fill.replace("%%TWEET_TIME%%", hours + " hours ago.");

                    // Append html string to table.#pitch-tbody HTML
                    tweet_html += template_fill.toString();
                }        
            });
            // Replace the pitch table body with the HTML we just generated.
            jQuery('#pitch-tbody').html(tweet_html);
        }
    );
});