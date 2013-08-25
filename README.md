webletter
=========
Webletter is a simple tool to create letters from tex templates.

This is still under development, this means many things won't work as you'd expect, e.g. the path to the php script is hardcoded.

A demo is available on http://halnt.dyndns.org/till/webletter/client/ .

Any patches, bug reports or pull requests are welcome.

Installation
---------
To install Webletter, simply copy both folders client and server to your webspace and put your template in template/ .
Your template should be called 'template.tex'. Then, visit http://your-server.com/path/to/webletter/client .

Requirements
---------
Note that webletter requires pdflatex to work properly.
On Debian, you will probably need the packages 
texlive-base, 
texlive-latex-base, 
texlive-latex-recommended, 
texlive-fonts-recommended.
