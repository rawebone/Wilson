# Wilson Documentation

Wilson is a lightweight web framework designed to provide a fast, consistent and
simple system for developing RESTful APIs. Its development principles are:

* **Performance**
  The framework does the least amount of work possible to get you from A to B
  and has been written to reduce the % of time consumed per request to its
  lowest level. As with any framework there is an overhead but there isn't
  a framework out there which offers the same concision and functionality
  out of the box without a major time penalty or requiring high availability
  environments.

* **Testability**
  A lot of Micro-frameworks focus more on the routing technology and not enough
  on writing tests. Wilson provides the ability to integrate with PHPUnit by
  default allowing you to create functional tests.
  
* **HTTP Best Practices**
  HTTP can be a real pain, providing the correct responses and handling
  errors/bad requests a hassle. When working on APIs, providing appropriate
  OPTIONS responses is a chore that gets in the way of your business logic.
  Caching is an annoyance. Wilson provides an efficient HTTP layer that
  is built of top of best practices established by Slim and Symfony.
  
* **Developer Happiness**
  Developer Experience (DX) has become a popular idea with Laravel and Symfony
  both being pioneers in that regard. As a Micro-framework, Wilson does not
  try to compete on that score, however because of the simplicity of its
  design and lack of conceptual overhead developers should be able to get up
  and running quickly and create maintainable systems.

The framework doesn't have very many concepts but they've been split out here
for ease of learning.

1. [Installation and Setup](installing.md)
2. [Request Lifecycle](lifecycle.md)
3. [Services](services.md)
4. [Testing](testing.md)
5. [Performance](performance.md)
6. [Internals](internals.md)
