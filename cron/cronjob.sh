#!/bin/bash
#  Warning:
#  -------
#  We recommend to exec this task each minute : */1 * * * *
#  You must replace NETWORK_URL by your project value.
#  You will probably need to adapt the commands with the real folder path of your project an WP-CLi binary

### Regular queue
wp content-sync-fusion queue get_sites --url="NETWORK_URL" | xargs -I {} wp content-sync-fusion queue pull --url={}

### Check for resync content (new site/blog)
wp content-sync-fusion resync new_sites --attachments=true --post_type=true --taxonomies=true --url="NETWORK_URL"

### Alternative queue
wp content-sync-fusion queue get_sites --alternativeq="true" --url="NETWORK_URL" | xargs -I {} wp content-sync-fusion queue pull --url={}  --alternativeq="true"