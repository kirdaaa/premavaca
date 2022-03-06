# Premavaca
You can execute this garbage at http://kirda.wtf/premavaca/. This website is not https and hackers will be able to steal the code you are sending through this website though. 😱

## Tutorial
### Bit sequences
Since the language must be confusing, there is no string identifiers, you can only use numbers, but not in decimal form, they need to be written in binary, however, we are not done yet, the binary must be written in form of `premavaca` where every capital letter refers to bit 1 and lowercase letter is bit 0.

Examples:
+ premaVAca -> 000001100 (12)
+ PREMAVACa -> 111111110 (510)

These numbers were 9-bit, but if you want a larger number there, you need to expand the bit sequence, that's done by writing `premavaca` again:
+ Premavacapremavaca -> 100000000000000000 (131072)

### Comments
Comments are created using the `?` symbol, there is no multiline comments.
```ini
? This is comment
?; I might use semicolon after the comment to make it work with `.ini` syntax

This no longer is, throws an error here!
```

### Callbacks
Premavaca can only do something through callbacks, these are kind of functions with parameters, in order to create a callback you use the `[]` operators. Inside of them there must be a number representing callback ID.
```ini
[premavaca] ? ID 0 is for `write` callback which writes raw numbers into output
```
This code won't do anything since we told it to write some numbers, but it does not know what numbers should it write, this is where you use parameters which are specified after the callback:
```ini
[premavaca]

PREMAVACA ?; Number that we want to write (PREMAVACA = 111111111 = 511)

```
> 511

Some callbacks require parameters, some accept infinite amount of them, useful callback is `premavacA` which writes ASCII characters:
```ini
[premavacA] ?; Callback ID 1 (write_ascii)

prEmaVaca ?; (prEmaVaca = 01001000 = 72  = H)
prEMavAcA ?; (prEMavAcA = 01100101 = 101 = e)
prEMaVAca ?; (prEMaVAca = 01101100 = 108 = l)
prEMaVAca ?; (prEMaVAca = 01101100 = 108 = l)
prEMaVACA ?; (prEMaVACA = 01101111 = 111 = o)
preMaVAca ?; (preMaVAca = 00101100 = 46  = ,)
preMavaca ?; (preMavaca = 00100000 = 32  =  )
prEmAvACa ?; (prEmAvACa = 01010110 = 86  = V)
prEMavacA ?; (prEMavacA = 01100001 = 96  = a)
prEMavaCA ?; (prEMavaCA = 01100011 = 99  = c)
prEMavacA ?; (prEMavacA = 01100001 = 96  = a)
preMavacA ?; (preMavacA = 00100001 = 33  = !)
```
> Hello, Vaca!

### Variables
Variables are created using callbacks, specifically callback `PREMAVACA`, it requires 2 parameters, variable name (in binary) and variable value:
```ini
[PREMAVACA] ?; Create variable
premavaca   ?; name = 0
preMAvaca   ?; value = 48

```
You can create as much variables as server can handle, in order to get value of variable, use the `*` operator:
```ini
[PREMAVACA] ?; Create variable
premavaca   ?; name = 0
preMAvaca   ?; value = 48

[premavacA] ?; Write ASCII characters
*premavaca  ?; Get value of variable with name 0 (in this case 48 = , in ASCII)
```
If variable with given name does not exist, it defaults to 0.

### Labels
Labels are created using callback `PremavacA`, it requires 1 parameter which is label ID.
```ini
[PremavacA] ?; Create label
premavaca   ?; ID = 0
```
To GOTO the label you use the `Premavaca` callback, it also requires 1 parameter which is label ID to which it should jump. For example the code below creates an infinite loop:
```ini
[PremavacA] ?; Create label
premavaca   ?; ID = 0

?; Writes 0 followed by 1 followed by 2
[premavaca] ?; Write plain numbers
premavaca   ?; Param #1 is 0
premavacA   ?; Param #2 is 1
premavaCa   ?; Param #3 is 2

?; Jumps to first line of code since that's where label 0 is defined
[Premavaca] ?; GOTO label
premavaca   ?; ID = 0

```
### High priority callbacks
Premavaca was not turing-complete because there was no way to implement `if` statements, actually, there was no way to skip section of code, it always would have to get executed, you could think of using labels to avoid that, however, this has an issue.
```ini
[Premavaca] ?; GOTO label
premavaca   ?; ID = 0

?; This code here should be ignored?

?; Write plain number 0
[premavaca]
premavaca

[PremavacA] ?; Define label
premavaca   ?; ID = 0
```
This code will throw an error `undefined label \`0\``, that is because the code executed from top to the bottom and at the time we tried to jump to label 0, it was not yet created.

The solution to this was implementing high priority callbacks which are executed first and only then normal callbacks are executed, to mark callback as high priority use the `@` operator:
```ini
[Premavaca] ?; GOTO label
premavaca   ?; ID = 0

?; This code here will be ignored

?; Write plain number 0
[premavaca]
premavaca

?; This callback is marked as high priority and thus it will be executed first
@[PremavacA] ?; Define label
premavaca   ?; ID = 0
```
Now everything works correctly, here is one more example using high priority callbacks:
```ini
?; This callback will be executed after the high priority callbacks are done
[premavaca]
premavaCA

?; This callback will be executed first
@[premavaca]
premavacA

?; This callback will be executed second
@[premavaca]
premavaCa
```
> 123

### Mathematical operations
Premavaca supports 5 basic mathematical operations, those are `+`, `-`, `*`, `/` and `%`, they all require 3 parameters, let's add a number to another number:
```ini
?; Create variable with name 0 and value 0
[PREMAVACA]
premavaca
premavaca

?; Create variable with name 1 and value 2, we are going to add this to name 0
[PREMAVACA]
premavacA
premavaCa

[premavaCa] ?; Callback for addition
premavaca   ?; Name of variable to which we want to add to
*premavacA  ?; How much we want to add to the variable
            ?; In this case we add value of `premavacA` variable

[premavaca] ?; Write plain numbers
*premavaca  ?; Value of variable `premavaca`

```
> 2

Same logic applies to the remaining 4 operators, note that `/` and `%` will throw "division by 0" error if parameter #2 is 0. Here are callback IDs for the operators:
* `+` -> premavaCa (000000010)
* `-` -> premavaCA (000000011)
* `*` -> premavAca (000000100)
* `/` -> premavAcA (000000101)
* `%` -> premavACa (000000110)

### Conditional GOTOs
Say you want to write number to the output only if it is equal to 0, you can do that using conditional GOTOs, they are GOTOs that will only jump to specific label if a condition is met. For example `PRemavaca` will only jump to label if parameter #2 is equal to parameter #3.
```ini
[PREMAVACA] ?; Create variable
premavaca   ?; Name = 0
premavacA   ?; Value = 1

[PRemavaca] ?; Jump to label if param #2 is equal to param #3
premavaca   ?; Jump to label with ID 0
*premavaca  ?; Param #2 (1)
premavaca   ?; Param #3 (0)

?; Write number 0 to the output, this will only run if previous callback did
?; not jump anywhere
[premavaca]
premavaca

@[PremavacA] ?; Create label
premavaca    ?; ID = 0

```
The code above will print `0`, however, if we replace `premavacA` on line 3 with `premavaca`, nothing will be printed.
