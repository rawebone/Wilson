# Internals

The following will document the principles guiding the development of Wilson
for those interested in how the performance has been attained and some of the
design choices that have been made.


## Guiding Principles

Writing a document like this is difficult because it becomes very difficult to
not to compare your work to other systems and in turn to sound like you are
slagging them off, like you've got nothing wrong yourself. As such, I'm going
to try to keep the comparisons as concise as possible.

Writing applications is difficult. The reason we turn to frameworks is to make
our lives easier by allowing us to focus on the task at hand while it abstracts
away the difficulty. The frameworks in turn have a number of factors to consider:

* Do their authors/users find more comfort in Domain Specific Language approaches
  or in the more traditional Monolithic MVC better suited for them
* Are they focused around rigid policies such as SOLID, or are they more pragmatic
* Are they targeting the enterprise or the enthusiast

In hunting the correct solution and trying to answer to the constraints of the
project, or sometimes by sheer ignorance, performance suffers. This is where
it gets tricky: __performance suffers__. What does that mean?

It is subjective. To me it means that the framework adds on overhead which gets
passed onto the end user without some kind of mitigation beyond environmental
changes. I've seen, on Twitter and in blogs, suggestions that if you want 
performance that you have two options:

* Don't use a framework (pure PHP is waaaay cooler, bro)
* You need to get a load balanced EC2 infrastructure with high availability
  database servers and PHP-FPM with OPCache, or why not switch to HHVM?
  
These basically push the problem onto the application engineer rather than
being dealt with by the frameworks themselves by optimising their processes.
In a number of cases the problem of performance is simply baked into the
design and the problems cannot be refactored out because of API stability
concerns.

This project is getting consistently low response times when compared to
pure PHP equivalents (in my test app, 0.002 seconds per request) while
not putting too many constraints upon developers; and this is the overall
guiding principle of development on this project.

A basic assumption is also made about the end users environment:

* Shared hosting environment
* PHP5.3
* No OPCaching

If we can get good performance under these kinds of conditions then we can
get even better when we have high performance environments.


## Performance

There are three major things to consider when looking at performance:

* The amount of objects involved
* The amount of method calls involved
* The amount of files involved

> It's important to note that this does not apply to every situation, and that
> these ideas have come out of testing and the constraints of the framework
> itself. Obviously some of these ideas are just good rules of thumb.

### Object Graph

The amount of objects is fairly simple. Anything loaded that is not directly
used in serving the request is a waste of time, and anything duplicated is
also a waste. During the lifecycle of the application there are 9 classes loaded
and 8 objects, at the maximum there are 10 objects in memory owned by the
framework. This keeps things nice and tight.

### Method Calls

Every time PHP has to jump the callstack, we are penalised. It doesn't amount
to much in and of itself but overall this does has an effect on the performance.
That said we cannot write an application without them, so instead we need to
break the problem down to simple need: if we get a performance benefit for
not doing the call we'll find a way not too.

A good example of this is the way the `Router` uses the `Cache`. It started life
as:

```php

    public function getTable()
    {
        if ($this->cache->has("router")) {
            return $this->cache->get("router");
        }
        
        // ...
    }

```

This is quite a common idea, it can be seen in lots of framework code, but it
is also slow because of the amount of duplicate work being done:

```php

    public function has($key)
    {
        return isset($this->state[$key]);
    }

    public function get($key)
    {
        if ($this->has($key)) {
            return $this->state[$key];
        }
        
        return null;
    }

```

Another point of sloppiness here is that there is no difference between checking
a null or an array for truthiness, so the fact that we are checking for bools makes
no sense. An XDebug Profile discovered this waste and after some refactoring
the implementation became:

```php

    public function getTable()
    {
        if (($table = $this->cache->get("router"))) {
            return $table;
        }
        
        // ...
    }

```

```php

    public function get($key)
    {
        // if ($this->has($key)) {
        if (isset($this->state[$key])) {
            return $this->state[$key];
        }
        
        return null;
    }

```

This small change saved about 1% off of the request time. Anywhere we can make
these kinds of changes - especially considering that very little of the internals
are exposed to the end user - we should. My goal is to make this codebase as lean
as possible.

### Files

Autoloading is great but by and large slow, even with OPCaching. As such we
have to offer the ability to compile the files that are needed for every
request to allow for best performance. There is `classpreloader` which
does this already but it has a lot of dependencies which are superfluous
when the script is fairly lightweight.

But it's something to bear in mind, and it's a challenge to the SOLID
approach- sometimes it makes more sense to put functionality in an
existing place.


### Other Thoughts

Lastly, but importantly:

* If we can avoid iterating over a set (especially a set of dynamic content)
  then we should. In the context of the framework, it's costly.
* If you can avoid jumping to userland code, more the better. The `Services`
  object is a good example of one method for doing this.


## Design Decisions

### Why another framework?

Performance. I need a good solid structure for my work and there were no
options which gave good enough performance.

### NIH HTTP Layer?

One of the benefits of Composer is that is allows us to easily share code.
One of the downsides is that those dependencies do not always share goals
of the project and so cannot be used. For example, `symfony/http-foundation`
was used prior to the creation of the HTTP layer but proved to be a real
weight to the framework; they have their own stuff and they have lots of
BC rules, maybe could be patched but probably not. Additionally, foundation
loads 9 classes if you just want 2, more than the size of the framework
at the time.

The only other option that made sense was the layer found in `slim/slim`.
It has a reputation for being pretty good, but wasn't standalone and so
has been forked for the purposes of this project. It keeps the project
dependency free, performant and has a solid base.

### Distinct lack of Get/Setters

They do the same job that we can do. They abstract details that often do
not really need to be abstracted; when these two do the same job, I know
which one I'm using:

```php

$api->resources = array("A");
$api->setResources(array("A"));

// ...

    public function setResources(array $resources)
    {
        $this->resources = $resources;
        return $this;
    }

```


