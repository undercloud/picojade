# picojade
Basic Jade Parser

```jade
!!! 5
html
  head
    title Examples
  body
    // comment
    <!-- html comment -->
    <?php $s = "Singleline php code" ?>
    
    h1 Header
    
    #block div with attr 'id' = block
    .classname div with attr 'class' = classname
    div#block.classname.another div combine attr's
    span(id="someid",class="classname",data-src="true") span with attr's list
    
    input(type="checkbox",value="self closing",checked)
    foo(data-src="force closing")/
    a(href="http://link.to"): img(src="/path/to") Link with image and text
    
    style.
      body {
        color: #aaa;
        font-size: 14px;
      }
      
    script.
      if(true)
        console.log("It's true")```
