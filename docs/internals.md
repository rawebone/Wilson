# Internals

This part of the documentation is intended for developers who want to understand
the methodologies used by the framework to reduce its footprint for performance
and to give an overview to anybody wanting to contribute.


## Guiding Principles

Firstly, it is worth noting that the Framework is virtually feature complete. As
such any change has to be viewed by its real value added versus performance lost
and so PR's may be rejected on that basis. The framework has a major goal of
small footprint and so everything that goes in must support that goal or it is
viewed as being superflous. Everything that follows explains the research that
went into the creation and optimisation of Wilson and will be expanded upon as
new techniques evolve.

The best thing to do when writing code is to assume some worst cases about your
end user:

* They are running a single server and cannot load balance
* They do not have the ability to use PHP-FPM
* They do not have an OPCache installed

If the code you write performs well under these conditions then that is a winner
because the performance you get when these techniques can be leveraged is very
high indeed and the lower the latency the better.


## Object Graphs

One of the simplest ways to judge efficiency in a system is to look at how many
objects are being created and what they are encapsulating to see what kind of
overheads you will be stuck with at runtime. Consider the following paradigm:

```php

$app->get("/a", function () {

});

```

This kind of example will be familiar to anyone whose worked with Micro-frameworks
and is widely used but we have a distinct problem here: every route has a cost of
two objects which have to exist at runtime. This means that your application has
causes allocations for `framework objects + 200 objects + business objects` to
define an API with 100 routes in it and that cannot be easily optimised.

Sure you can use an OPCache to reduce the parse time but you still end up with
the same memory allocations being made. At best 1 of the 100 routes will be
matched, meaning you have massive waste before you've even started.

This isn't to say that this paradigm cannot work, indeed in small systems it
can. But most systems are not small. If you assume an API has five resources
and at least the basic operations are supported you are looking at 25 end
points which will end up at `framework objects + 50 objects + business objects`;
it gets messy quickly.

It is worth noting that Slim has recently taken a position of allowing a
class method definition of:

```php

$app->get("/a", "Class:method");

```

This definitely helps but creates a new problem - the route definition is
placed farther from the Controller that reacts to it. Some sources, such
as the excellent "Build APIs You Won't Hate" by Phil Sturgeon suggest that
this allows you to easily identify the URL's in your application; I'll
accept that this is true to a point, but if you have a well defined names
for your resources and controllers, it makes just as much sense to keep
it in annotations.

I'd make a detailed point about the Monolithic frameworks here but they imbue
so much complexity that it is easier to say that Symfony Http Foundation is
twice the size of Wilson and at runtime creates nearly as many objects if
you only choose to use its Request and Response objects. Http Foundation
was the original library used by Wilson but proved to be much to heavy.

The approach taken with Wilson is to keep the object graph tight: `framework +
1 object + business objects` will always be the case. Because Controllers are
assigned to the object there is a negligible memory overhead for loading them,
if you are autoloading the Controllers even less so. Because the framework
creates the Resource object it will only ever load a single instance. 


## Superflous Method Calls

Every time a function is called in PHP there is a measurable cost. It might
be small but on the grand scale this starts to add up. Going back to our
example of the Micro-framework paradigm, if we want to add conditions to
the route:

```php

$app->get("/users/{id}", function ()
{

})->conditions(array("id" => "\d+"));

```

Firstly, this is cumbersome syntactically as the Controller gets somewhat lost.
As the application grows in complexity this spikes. But the biggest thing is
the amount of method calls being made. In Slim this equates to:

```php

$app->get()->method()->conditions();

```

The `conditions` call itself is a setter and so is essentially:

```php

function conditions(array $conds)
{
    $this->conds = $conds;
    return $this;
}

```

Because you have to jump the call stack, setters in PHP are expensive. As such
Wilson is designed for pragmatism rather than safety by espousing that users
should directly modify the state of the API object, for example:

```php

$api = new Wilson\Api();
$api->resources = array("Users");

```

There is no safety in this approach- if one were to assign a string to resources
it will break, for example. But with the right documentation you can teach people
to avoid this pitfall easily and in the process remove a superflous setter.

There are additional cases. The Pimple Service Locator is a model for how Slim
and Silex handle SL but they create a problem: they act like an array over an
array. Taking the stance that function calls are expensive, this is a disaster:

```php

class Container
{
    protected $services = [];
    
    public function offsetExists($blah)
    {
        return isset($this->services[$blah]);    
    }
}

```

You are jumping the call stack to make an assertion that PHP can already make.
Admittedly this is all in support of functionality that allows third parties
to contribute pre-configured service containers and an array or such structure
is very good at this; however it is not performant.

The approach taken with the Service container in Wilson is very different:
a user extends out a base object with methods that return objects or values.
These are then assigned directly to properties of the container object which
means that the next time the service is required PHP does not have to jump
to Userland code at all for the same check to be made. Uniform API, good
performance. Again, no closures are used which means that negligible memory
is consumed in doing so.

Additionally, we have to weigh up conventional wisdom about object methods
when thinking about high performance. The Router, for example, has a method
called `parseAnnotation` which started life as four methods and its own
object. This would be the correct way in a fully BDD world with SRP used to
its nth but does not always make sense. Sometimes we have to step away
from objects as real things and see them more a logical grouping of
parts of an algorithm.

Of course this isn't to say that function calls are bad: they are a necessity
for creating maintainable code. However we have to weigh up what is logically
maintainable versus performance. There are other ways through which one can
avoid the method call performance issue which is to use returns. The Router 
checks for a cached table which used to look like this:

```php

if ($this->cache->has("router")) {
    return $this->cache->get("router");
}

```

However, as the `get` method is already performing the check it is logically
invalid to make the call to `has`. Additionally as the default is null we
can easily use the return value as the condition which is faster than the
two method calls:

```php

if (($table = $this->cache->get("router"))) {
    return $table;
}

```

Additionally, now we know that `has` is moot in the API we can remove it
and inline the check it performed:

```php

function get($name)
{
    // if ($this->has($name)) {
    if (isset($this->state[$name])) {
        return $this->state[$name]
    }
    
    return null;
}

```

Performance improves and the code is as clean as before.


## Autoloading

Autoloading is a brilliant thing, I'm not a naysayer. However it is slow;
in a test application Composers autoloader with optimisation accounted for
nearly 12% of the request processing time.
