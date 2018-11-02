/Dues Paid Through/	{ flag = 1; next; }

	{
	if (!flag)  next; # Skip header
	gsub(/"/,"");
	if ($1=="") next; # Skip empty lines
	if ($2=="QUIT") next; # Skip ex members
	if ($2=="SK") next; # Skip silent keys

	if ($1+0==$1)	# If first column is a row number
		print $3
	}
