# cloudflare_bulk_ip
This is a scrip that will find a range of IPs across your cloudflare zones and replace them with another range of IP addresses. I use this primarily to swap back and forth between two sets of load balancers when doing maintenance work.

## Instructions for use
The first thing to do is to add ranges of the IPs that you wish to find and replace. These are commented in the file near lines 7-30. Next, add your Cloudflare auth email and API key on lines 39 and 40. With those credentials, you should be able to now run the script.

## Usage
Place this script where you can reach it easily from the command line. Then run it from the PHP CLI:

`user$ /usr/bin/php cloudflare.php low`
This will swap to the low range of IPs given.

`user$ /usr/bin/php cloudflare.php high`
This will swap to the high range of IPs given.
