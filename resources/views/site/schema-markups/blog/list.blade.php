<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Blog",
  "name": "{{$meta_title ?? 'Bloqlar'}}",
  "description": "{{ $meta_description ?? 'Rəqəmsal inkişaf, veb texnologiyalar, mobil tətbiqlər, UI/UX dizayn və biznes üçün innovativ həllər haqqında Proweb komandası tərəfindən hazırlanan məqalələrlə tanış olun.' }}",
  "url": "{{request()->fullUrl()}}",
  "publisher": {
    "@type": "Organization",
    "name": "{{$company_name}}",
    "url": "{{url($currentLocale)}}"
  },
  "blogPost": {
    "@type": "ItemList",
    "itemListElement": [
      @foreach ($schema_blogs as $schema_blog)
      {
        "@type": "ListItem",
        "position": {{$loop->iteration}},
        "url": "{{$schema_blog->single_url}}"
      }{{!$loop->last ? ',' : null}}
      @endforeach
    ]
  }
}
</script>
