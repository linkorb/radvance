# Permissions

Permissions in Radvance are inspired by Amazon's security policies

## XRNs

All 'resources' (objects, records, anything) in Radvance are identified by their 'XRN'.

An XRN (Cross-app Resource Name) is a string that uniquely identifies a 'resource'.
It is inspired by Amazon's ARNs [read more](http://docs.aws.amazon.com/general/latest/gr/aws-arns-and-namespaces.html)

Valid formats:

    xrn:partition:service:region:account-id:resource-id
    xrn:partition:service:region:account-id:resource-type/resource-id
    xrn:partition:service:region:account-id:resource-type:resource-id

For example:

    xrn:linkorb:userbase:eu::account/linkorb

Breaking this string down by it's semi-colons:

* xrn: standard prefix for all XRNs
* linkorb: the partition of this resource
* userbase: a resource in the "userbase" service
* eu: the region of this resource. In case the service is split into region, this contains a region name. Otherwise it remains empty.
* account/linkorb: This indicates the resource is of type 'account', with identifier 'linkorb'
