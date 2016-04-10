# picojade
Basic Jade Parser

##install
```composer require undercloud/picojade```

##usage
```PHP
  require 'vendor/autoload.php';
  
  $jade = new Undercloud\PicoJade;
  $template = file_get_contents(__DIR__ . '/index.jade');
  
  echo $jade->compile($template);
```

##doctype
```!!! 5```  
Avail values  
* 5
* xml
* default
* transitional
* strict
* frameset
* 1.1
* basic
* mobile

##comment
```<!-- html comment -->```  
```//single comment```  
```Jade
// multi
   line
   comment
```

##tag
`h1 Header`  
`#block`  
`.classname`  
`div\#block.classname.another`

##single
```input(type="checkbox" value="self closing" checked)```  
```foo(data-src="force closing")/```  
```a(href="http://link.to"): img(src="/path/to") Link with image and text```  

##text
```p Lorem ipsum```  
```Jade
script.
  if(true)
    console.log("It's true")
```

##attr
```span(id="someid" class="classname" data-src="true")```  

##php
```PHP
<?php $s = "Singleline php code" ?>
```