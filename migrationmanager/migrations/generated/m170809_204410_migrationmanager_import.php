<?php
namespace Craft;

/**
 * Generated migration
 */
class m170809_204410_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $json = '{"settings":{"dependencies":[],"elements":[]},"content":{"entries":[{"slug":"homepage","section":"homepage","locales":{"en_us":{"slug":"homepage","section":"homepage","enabled":"1","locale":"en_us","localeEnabled":"1","postDate":{"date":"2017-08-09 20:27:54.000000","timezone_type":3,"timezone":"UTC"},"expiryDate":null,"title":"Welcome to Craft.dev!","entryType":"homepage","body":"<p>Vivamus posuere tempor tortor id molestie. Nam ut auctor mauris. Etiam consectetur fringilla lectus, in volutpat urna vulputate eget. Nunc sed metus lacus. Aenean vitae augue ligula. Fusce rutrum pharetra ullamcorper. Nulla sem velit, mollis quis velit ut, cursus fermentum augue. In eros lorem, ullamcorper et mattis eleifend, egestas eu nisi.<\/p>\n<p>Vestibulum sed augue nibh. Donec ut leo justo. Curabitur elementum est eu tincidunt molestie. Phasellus est quam, hendrerit at venenatis at, commodo vel felis. Aenean imperdiet, dolor nec tristique auctor, magna massa interdum turpis, a mattis orci enim nec felis. Maecenas risus metus, faucibus a ligula et, dictum condimentum justo. Suspendisse bibendum sagittis ex, non finibus orci congue at. Cras varius consequat felis, et hendrerit odio tempor sed. Sed venenatis felis ante, pretium facilisis augue aliquet at. Mauris semper augue ac enim lacinia, sit amet elementum leo aliquet. Maecenas vel convallis erat, sollicitudin imperdiet velit. Aliquam nisl lectus, euismod egestas diam ac, vestibulum ultrices nunc. Donec vitae sodales risus. Aliquam finibus a leo ac imperdiet.<\/p>","asset":[{"elementType":"Asset","filename":"jesse-gardner-40005.jpg","folder":"Local","source":"local"}]},"es_us":{"slug":"homepage","section":"homepage","enabled":"1","locale":"es_us","localeEnabled":"1","postDate":{"date":"2017-08-09 20:27:54.000000","timezone_type":3,"timezone":"UTC"},"expiryDate":null,"title":"Welcome to Craft.dev!","entryType":"homepage","body":"<p>It\u2019s true, this site doesn\u2019t have a whole lot of content yet, but don\u2019t worry. Our web developers have just installed the CMS, and they\u2019re setting things up for the content editors this very moment. Soon Craft.dev will be an oasis of fresh perspectives, sharp analyses, and astute opinions that will keep you coming back again and again.<\/p>","asset":[{"elementType":"Asset","filename":"jesse-gardner-40005.jpg","folder":"Local","source":"local"}]}}}]}}';
        return craft()->migrationManager_migrations->import($json);
    }

}
