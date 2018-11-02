/^====/	{ next; }

/^Call *HSC/	{ flag = 1; next; }

	{
	if (!flag)  next; # Skip header
	gsub(/^ .$/, ""); 
	sub(//, "");
	if ($1=="") next; # Skip empty lines
	gsub("\xD8", "0"); # Slashed zero to normal zero
	gsub("\x9D", "0"); # Slashed zero to normal zero
	sub(/\*/, ""); # Remove * character that sometimes occurs

	sub(/PA3C V V/, "PA3CVV"); # Correct error in members file
	sub(/HS0\/          OZ1HET/, "HS0/OZ1HET"); # Correct another error
	sub(/9A7WW 4O3AA J28AA 6O3A KB3WVX/, "9A7WW, 4O3AA, J28AA, 6O3A, KB3WVX")
	gsub(" ", "", $1); # Remove spaces that sometimes occur

	#othercalls=substr($0, 40, 27);
	othercalls=substr($0, 39, 32);
	gsub(" ", "", othercalls); # Remove spaces
	if (length(othercalls)>0) { # Member has second call(s)
	   split (othercalls, arr, ",");
	} else {
	   delete arr
	}
	
	print $1
	for (a in arr)
	   print arr[a]
	}

