# Item Creation Example

## Example Request for Experience Item

```json
{
  "name": "Dsad",
  "description": "Dadsa",
  "price": "44",
  "contact": "123123",
  "video_link": "",
  "category_id": "2",
  "provider_item_type": "experience"
}
```

## Required Fields
- name
- category_id
- price

## Optional Fields
- description
- address
- contact
- show_only_to_premium
- video_link
- image (file upload)
- gallery_images (array of file uploads)
- country
- state
- city
- custom_fields (JSON)
- custom_field_files (array of file uploads)
- slug
- provider_item_type (service or experience, defaults to service) 