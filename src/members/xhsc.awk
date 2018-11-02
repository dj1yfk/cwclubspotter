BEGIN	{ FS=";" }

	{
	if (index($0, ";")==0) next; # Skip lines without data in call;nr form
	print $1
	}
