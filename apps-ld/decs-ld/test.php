<?php
header('Content-Type: application/JSON');  
$ctx='{
    "meshv": "http://id.nlm.nih.gov/mesh/vocab#", 
    "schema": "http://schema.org/",
    "CheckTag": {"@id": "meshv:CheckTag"},
    "Concept": {"@id": "meshv:Concept"},
    "Descriptor": {"@id": "meshv:Descriptor"},
    "DescriptorQualifierPair": {"@id": "meshv:DescriptorQualifierPair"},
    "PublicationType": {"@id": "meshv:PublicationType"},
    "Qualifier": {"@id": "meshv:Qualifier"},
    "SupplementaryConceptRecord": {"@id": "meshv:SupplementaryConceptRecord"},
    "Term": {"@id": "meshv:Term"},
    "TopicalDescriptor": {"@id": "meshv:TopicalDescriptor"},
    "TreeNumber": {"@id": "meshv:TreeNumber"},
    "allowableQualifier": {"@id": "meshv:allowableQualifier","@type": "@id"},
    "broaderConcept": {"@id": "meshv:broaderConcept","@type": "@id"},
    "broaderDescriptor": {"@id": "meshv:broaderDescriptor","@type": "@id"},
    "broaderQualifier": {"@id": "meshv:broaderQualifier","@type": "@id"},
    "concept": {"@id": "meshv:concept","@type": "@id"},
    "hasDescriptor": {"@id": "meshv:hasDescriptor","@type": "@id"},
    "hasQualifier": {"@id": "meshv:hasQualifier","@type": "@id"},
    "indexerConsiderAlso": {"@id": "meshv:indexerConsiderAlso","@type": "@id"},
    "mappedTo": {"@id": "meshv:mappedTo","@type": "@id"},
    "narrowerConcept": {"@id": "meshv:narrowerConcept","@type": "@id"},
    "parentTreeNumber": {"@id": "meshv:parentTreeNumber","@type": "@id"},
    "pharmacologicalAction": {"@id": "meshv:pharmacologicalAction","@type": "@id"},
    "preferredConcept": {"@id": "meshv:preferredConcept","@type": "@id"},
    "preferredMappedTo": {"@id": "meshv:preferredMappedTo","@type": "@id"},
    "preferredTerm": {"@id": "meshv:preferredTerm","@type": "@id"},
    "relatedConcept": {"@id": "meshv:relatedConcept","@type": "@id"},
    "seeAlso": {"@id": "meshv:seeAlso","@type": "@id"},
    "term": {"@id": "meshv:term","@type": "@id"},
    "treeNumber": {"@id": "meshv:treeNumber","@type": "@id"},
    "useInstead": {"@id": "meshv:useInstead","@type": "@id"},
    "abbreviation": {"@id": "meshv:abbreviation"},
    "active": {"@id": "meshv:active"},
    "altLabel": {"@id": "meshv:altLabel"},
    "annotation": {"@id": "meshv:annotation"},
    "casn1_label": {"@id": "meshv:casn1_label"},
    "considerAlso": {"@id": "meshv:considerAlso"},
    "dateCreated": {"@id": "meshv:dateCreated"},
    "dateEstablished": {"@id": "meshv:dateEstablished"},
    "dateRevised": {"@id": "meshv:dateRevised"},
    "entryVersion": {"@id": "meshv:entryVersion"},
    "frequency": {"@id": "meshv:frequency"},
    "historyNote": {"@id": "meshv:historyNote"},
    "identifier": {"@id": "meshv:identifier"},
    "lastActiveYear": {"@id": "meshv:lastActiveYear"},
    "lexicalTag": {"@id": "meshv:lexicalTag"},
    "nlmClassificationNumber": {"@id": "meshv:nlmClassificationNumber"},
    "note": {"@id": "meshv:note"},
    "onlineNote": {"@id": "meshv:onlineNote"},
    "prefLabel": {"@id": "meshv:prefLabel"},
    "previousIndexing": {"@id": "meshv:previousIndexing"},
    "publicMeSHNote": {"@id": "meshv:publicMeSHNote"},
    "registryNumber": {"@id": "meshv:registryNumber"},
    "relatedRegistryNumber": {"@id": "meshv:relatedRegistryNumber"},
    "scopeNote": {"@id": "meshv:scopeNote"},
    "sortVersion": {"@id": "meshv:sortVersion"},
    "source": {"@id": "meshv:source"},
    "thesaurusID": {"@id": "meshv:thesaurusID"},
    "sameAs": { "@id": "schema:sameAs", "@type": "@id"}
 }';      
 $ctx_arr = json_decode($ctx,1);
 $ctx = json_encode (array('@context'=>$ctx_arr));
$dbnameD = 'decs';
$link_decs = mysqli_connect('localhost', 'root', 'root', $dbnameD);
if (!$link_decs) {
    die('Could not connect DeCS: ' . mysqli_connect_errno());
} 
$sql = "SELECT data FROM descriptor WHERE id=31703";
$result = mysqli_query($link_decs, $sql);          
$row = mysqli_fetch_assoc($result);
    $descr = substr($ctx,0,-1) .",". substr($row['data'],1);
echo $descr;  
  
?>
