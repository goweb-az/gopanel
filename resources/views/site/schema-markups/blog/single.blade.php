<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "{{$meta_title ?? 'Bloqlar'}}",
  "description": "{{ $meta_description ?? 'Rəqəmsal inkişaf, veb texnologiyalar, mobil tətbiqlər, UI/UX dizayn və biznes üçün innovativ həllər haqqında Proweb komandası tərəfindən hazırlanan məqalələrlə tanış olun.' }}",
  "image": {
    "@type": "ImageObject",
    "url": "{{$schema_blog->image_url}}",
    "width": 1200,
    "height": 630
  },
  "author": {
    "@type": "Organization",
    "name": "{{$company_name}}",
    "url": "{{url($currentLocale)}}"
  },
  "publisher": {
    "@type": "Organization",
    "name": "{{$company_name}}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{$siteSettings->logo_light_url}}",
      "width": 300,
      "height": 60
    }
  },
  "datePublished": "{{ $schema_blog?->created_at?->toDateString() }}",
  "dateModified": "{{ $schema_blog?->updated_at?->toDateString() }}",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{request()->fullUrl()}}"
  }
}
</script>
