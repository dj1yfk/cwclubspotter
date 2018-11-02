/^#/	{ # Skip commentary
	next
	}

	{
	if ($1=="") next; # Skip empty lines
	gsub("\r",""); # Correct DOS cr lf in http://foc.dj1yfk.de/members.txt
	print $1
	}
