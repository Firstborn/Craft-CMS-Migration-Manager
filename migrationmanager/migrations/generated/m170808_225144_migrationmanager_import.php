<?php
namespace Craft;

/**
 * Generated migration
 */
class m170808_225144_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $json = '{"settings":{"dependencies":[],"elements":[]},"content":{"entries":[{"slug":"blog-test","section":"blog","locales":{"en_us":{"slug":"blog-test","section":"blog","enabled":"1","locale":"en_us","localeEnabled":"1","postDate":{"date":"2017-08-07 19:39:00.000000","timezone_type":3,"timezone":"UTC"},"expiryDate":null,"title":"Blog test ttt","entryType":"blog","body":"<p>this is english<\/p>","superTable":{"new1":{"type":1,"fields":{"tTitle":"english","tLink":{"type":"custom","value":"http:\/\/yahoo.com","defaultText":"","customText":false,"target":false,"custom":"http:\/\/yahoo.com"}}}},"asset":[{"elementType":"Asset","filename":"image1.jpg","folder":"Uploads","source":"uploads"}],"category":[{"elementType":"Category","slug":"a-category","category":"categoryGroup"}],"entry":[{"elementType":"Entry","slug":"homepage","section":"homepage"}],"user":[{"elementType":"User","username":"admin"}],"tags":[]},"es_us":{"slug":"blog-test","section":"blog","enabled":"1","locale":"es_us","localeEnabled":"0","postDate":{"date":"2017-08-07 19:39:00.000000","timezone_type":3,"timezone":"UTC"},"expiryDate":null,"title":"Blog test","entryType":"blog","body":"<p>this is spanish<\/p>","superTable":{"new1":{"type":1,"fields":{"tTitle":"spanish","tLink":{"type":"custom","value":"http:\/\/yahoo.es","defaultText":"","customText":false,"target":false,"custom":"http:\/\/yahoo.es"}}}},"asset":[{"elementType":"Asset","filename":"image1.jpg","folder":"Uploads","source":"uploads"}],"category":[{"elementType":"Category","slug":"a-category","category":"categoryGroup"}],"entry":[],"user":[{"elementType":"User","username":"admin"}],"tags":[]},"en_ca":{"slug":"blog-test","section":"blog","enabled":"1","locale":"en_ca","localeEnabled":"0","postDate":{"date":"2017-08-07 19:39:00.000000","timezone_type":3,"timezone":"UTC"},"expiryDate":null,"title":"Blog test","entryType":"blog","body":"<p>canadian eh<\/p>","superTable":[],"asset":[{"elementType":"Asset","filename":"image1.jpg","folder":"Uploads","source":"uploads"}],"category":[{"elementType":"Category","slug":"a-category","category":"categoryGroup"}],"entry":[],"user":[{"elementType":"User","username":"admin"}],"tags":[]}}}]}}';
        return craft()->migrationManager_migrations->import($json);
    }

}
