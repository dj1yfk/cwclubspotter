
# <li><a href="http://twitter.com/M0SCU" target="_blank" rel="noopener">Stew M0SCU</a> #014</li>

while ($line = <>) {
    if ($line =~ /\s([A-Z0-9\/]{4,})/) {
        @a = split(/\//, $1);
        print join("\n", @a)."\n";
    }
}
