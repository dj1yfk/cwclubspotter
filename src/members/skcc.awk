/<tr/	{ flag=0; next } # New member record
/<td/	{ gsub(/<[^>]*>/,"");
	  flag++; 
	  if (flag==2 && $0!~"^$") print; # Column 2 is the call sign
	  else if (flag==6 && $0!~"^$") { # Column 6 is the extra call signs
	     gsub(/ /, ""); 
	     split($0, arr, /,/); 
	     for (i in arr) print arr[i];
	     }
	  next } 
