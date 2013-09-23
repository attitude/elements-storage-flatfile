Element: Storage in Files
=========================

###### Element: Storage, Flat-file implementation

Storage in file build with only basic set of API calls similar to [Memcached](http://memcached.org "A distributed memory object caching system"):

- exists()
- get()
- add()
- set()
- replace()
- delete()

Uses Serialiser to store data in file.

Implementation:

- Blob Storage Element - store any object or data in file.
  - `_id` - UUID hash
  - `_created` - file created unix timestamp
  - `_updated` - file modified uni timestamp
  - other object data

**Enjoy!**

[@martin_adamko](http://twitter.com/martin_adamko)  
*Say hi on Twitter*