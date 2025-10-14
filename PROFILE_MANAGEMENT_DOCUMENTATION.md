# 👤 Profile Management API Documentation

Complete guide for managing user profiles, including uploading profile pictures and updating profile information.

---

## 📋 Overview

The profile management system allows users to:

-   ✅ Update profile information (name, username, bio, profession)
-   ✅ Upload/update profile picture
-   ✅ Manage interests
-   ✅ View profile information
-   ✅ Search users by interests or profession

**Storage:** Profile pictures are stored on AWS S3 with CDN support for fast loading.

---

## 🔄 Profile Management Endpoints

### 1. Update Profile (Including Profile Picture)

**Endpoint:** `PUT /api/users/profile`

**Authentication:** Required (Bearer Token)

**Content-Type:** `multipart/form-data` (when uploading image)

**Request Body:**

```json
{
    "full_name": "John Doe",
    "username": "johndoe123",
    "bio": "Hair stylist and beauty enthusiast 💇‍♀️",
    "profession": "Professional Hair Stylist",
    "interests": ["Hair Styling", "Makeup", "Fashion"],
    "profile_picture": "<file>"
}
```

**Field Details:**

-   `full_name` (optional, string, max: 255) - User's full name
-   `username` (optional, string, max: 255) - Unique username
-   `bio` (optional, string, max: 500) - User biography/description
-   `profession` (optional, string, max: 255) - User's profession
-   `interests` (optional, array) - Array of interest strings (max 50 chars each)
-   `profile_picture` (optional, file) - Image file (JPEG, PNG, JPG, GIF, max: 2MB)

**Success Response (200):**

```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "full_name": "John Doe",
        "username": "johndoe123",
        "email": "john@example.com",
        "bio": "Hair stylist and beauty enthusiast 💇‍♀️",
        "profession": "Professional Hair Stylist",
        "profile_picture": "https://cdn.example.com/profiles/abc123.jpg",
        "interests": ["Hair Styling", "Makeup", "Fashion"],
        "is_business": false,
        "followers_count": 150,
        "following_count": 80,
        "posts_count": 45,
        "created_at": "2025-01-15T10:30:00.000000Z",
        "updated_at": "2025-10-14T12:00:00.000000Z"
    }
}
```

**Error Responses:**

**Validation Error (422):**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "username": ["The username has already been taken."],
        "profile_picture": ["The profile picture must be an image."],
        "bio": ["The bio must not be greater than 500 characters."]
    }
}
```

**Unauthorized (401):**

```json
{
    "message": "Unauthenticated."
}
```

---

## 💻 Implementation Examples

### React/React Native - Update Profile with Picture

```javascript
const updateProfile = async (profileData, profilePictureUri = null) => {
    try {
        const formData = new FormData();

        // Add text fields
        if (profileData.full_name)
            formData.append("full_name", profileData.full_name);
        if (profileData.username)
            formData.append("username", profileData.username);
        if (profileData.bio) formData.append("bio", profileData.bio);
        if (profileData.profession)
            formData.append("profession", profileData.profession);

        // Add interests array
        if (profileData.interests && profileData.interests.length > 0) {
            profileData.interests.forEach((interest, index) => {
                formData.append(`interests[${index}]`, interest);
            });
        }

        // Add profile picture if provided
        if (profilePictureUri) {
            // For React Native
            formData.append("profile_picture", {
                uri: profilePictureUri,
                type: "image/jpeg", // or detect from file
                name: "profile.jpg",
            });

            // For Web (with file input)
            // const file = document.getElementById('fileInput').files[0];
            // formData.append('profile_picture', file);
        }

        const response = await fetch("https://your-api.com/api/users/profile", {
            method: "PUT",
            headers: {
                Authorization: `Bearer ${token}`,
                // Note: Don't set Content-Type header, let browser/fetch set it with boundary
            },
            body: formData,
        });

        const data = await response.json();

        if (response.ok) {
            console.log("Profile updated:", data.data);
            // Update local state with new profile data
            return data.data;
        } else {
            console.error("Error:", data.message);
            throw new Error(data.message);
        }
    } catch (error) {
        console.error("Network error:", error);
        throw error;
    }
};

// Usage example
const handleUpdateProfile = async () => {
    const profileData = {
        full_name: "John Doe",
        username: "johndoe123",
        bio: "Hair stylist and beauty enthusiast",
        profession: "Professional Hair Stylist",
        interests: ["Hair Styling", "Makeup", "Fashion"],
    };

    const imageUri = "file:///path/to/image.jpg"; // or from image picker

    try {
        const updatedProfile = await updateProfile(profileData, imageUri);
        alert("Profile updated successfully!");
    } catch (error) {
        alert("Failed to update profile");
    }
};
```

---

### React Native - With Image Picker

```javascript
import React, { useState } from "react";
import { Button, Image, View } from "react-native";
import * as ImagePicker from "expo-image-picker"; // or react-native-image-picker

const ProfileEditScreen = () => {
    const [profileData, setProfileData] = useState({
        full_name: "",
        username: "",
        bio: "",
        profession: "",
        interests: [],
    });
    const [imageUri, setImageUri] = useState(null);

    const pickImage = async () => {
        // Request permissions
        const { status } =
            await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (status !== "granted") {
            alert("Sorry, we need camera roll permissions!");
            return;
        }

        // Pick image
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.Images,
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.8,
        });

        if (!result.canceled) {
            setImageUri(result.assets[0].uri);
        }
    };

    const updateProfile = async () => {
        const formData = new FormData();

        // Add fields
        formData.append("full_name", profileData.full_name);
        formData.append("username", profileData.username);
        formData.append("bio", profileData.bio);
        formData.append("profession", profileData.profession);

        profileData.interests.forEach((interest, index) => {
            formData.append(`interests[${index}]`, interest);
        });

        // Add image if selected
        if (imageUri) {
            const filename = imageUri.split("/").pop();
            const match = /\.(\w+)$/.exec(filename);
            const type = match ? `image/${match[1]}` : "image/jpeg";

            formData.append("profile_picture", {
                uri: imageUri,
                name: filename,
                type,
            });
        }

        try {
            const response = await fetch(
                "https://your-api.com/api/users/profile",
                {
                    method: "PUT",
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                    body: formData,
                }
            );

            const data = await response.json();

            if (response.ok) {
                alert("Profile updated successfully!");
            } else {
                alert(data.message || "Update failed");
            }
        } catch (error) {
            alert("Network error");
        }
    };

    return (
        <View>
            {imageUri && (
                <Image
                    source={{ uri: imageUri }}
                    style={{ width: 200, height: 200 }}
                />
            )}
            <Button title="Pick Profile Picture" onPress={pickImage} />
            <Button title="Update Profile" onPress={updateProfile} />
        </View>
    );
};
```

---

### Flutter/Dart - Update Profile

```dart
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

Future<void> updateProfile({
  String? fullName,
  String? username,
  String? bio,
  String? profession,
  List<String>? interests,
  File? profilePicture,
  required String token,
}) async {
  var uri = Uri.parse('https://your-api.com/api/users/profile');
  var request = http.MultipartRequest('PUT', uri);

  // Add headers
  request.headers['Authorization'] = 'Bearer $token';

  // Add text fields
  if (fullName != null) request.fields['full_name'] = fullName;
  if (username != null) request.fields['username'] = username;
  if (bio != null) request.fields['bio'] = bio;
  if (profession != null) request.fields['profession'] = profession;

  // Add interests
  if (interests != null) {
    for (int i = 0; i < interests.length; i++) {
      request.fields['interests[$i]'] = interests[i];
    }
  }

  // Add profile picture
  if (profilePicture != null) {
    var stream = http.ByteStream(profilePicture.openRead());
    var length = await profilePicture.length();
    var multipartFile = http.MultipartFile(
      'profile_picture',
      stream,
      length,
      filename: profilePicture.path.split('/').last,
    );
    request.files.add(multipartFile);
  }

  // Send request
  var response = await request.send();
  var responseData = await response.stream.bytesToString();

  if (response.statusCode == 200) {
    print('Profile updated successfully');
    print(responseData);
  } else {
    print('Error: $responseData');
  }
}

// Usage with image picker
Future<void> pickAndUpdateProfilePicture() async {
  final ImagePicker picker = ImagePicker();
  final XFile? image = await picker.pickImage(source: ImageSource.gallery);

  if (image != null) {
    await updateProfile(
      fullName: 'John Doe',
      username: 'johndoe123',
      bio: 'Hair stylist',
      profession: 'Professional Hair Stylist',
      interests: ['Hair Styling', 'Makeup'],
      profilePicture: File(image.path),
      token: 'your-auth-token',
    );
  }
}
```

---

### iOS - Swift - Update Profile

```swift
import Foundation
import UIKit

func updateProfile(
    fullName: String?,
    username: String?,
    bio: String?,
    profession: String?,
    interests: [String]?,
    profileImage: UIImage?,
    token: String,
    completion: @escaping (Bool, String) -> Void
) {
    let url = URL(string: "https://your-api.com/api/users/profile")!
    var request = URLRequest(url: url)
    request.httpMethod = "PUT"
    request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")

    let boundary = UUID().uuidString
    request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")

    var body = Data()

    // Add text fields
    if let fullName = fullName {
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"full_name\"\r\n\r\n".data(using: .utf8)!)
        body.append("\(fullName)\r\n".data(using: .utf8)!)
    }

    if let username = username {
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"username\"\r\n\r\n".data(using: .utf8)!)
        body.append("\(username)\r\n".data(using: .utf8)!)
    }

    if let bio = bio {
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"bio\"\r\n\r\n".data(using: .utf8)!)
        body.append("\(bio)\r\n".data(using: .utf8)!)
    }

    if let profession = profession {
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"profession\"\r\n\r\n".data(using: .utf8)!)
        body.append("\(profession)\r\n".data(using: .utf8)!)
    }

    // Add interests
    if let interests = interests {
        for (index, interest) in interests.enumerated() {
            body.append("--\(boundary)\r\n".data(using: .utf8)!)
            body.append("Content-Disposition: form-data; name=\"interests[\(index)]\"\r\n\r\n".data(using: .utf8)!)
            body.append("\(interest)\r\n".data(using: .utf8)!)
        }
    }

    // Add profile image
    if let image = profileImage, let imageData = image.jpegData(compressionQuality: 0.8) {
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"profile_picture\"; filename=\"profile.jpg\"\r\n".data(using: .utf8)!)
        body.append("Content-Type: image/jpeg\r\n\r\n".data(using: .utf8)!)
        body.append(imageData)
        body.append("\r\n".data(using: .utf8)!)
    }

    body.append("--\(boundary)--\r\n".data(using: .utf8)!)
    request.httpBody = body

    URLSession.shared.dataTask(with: request) { data, response, error in
        guard let data = data,
              let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let success = json["success"] as? Bool,
              let message = json["message"] as? String else {
            completion(false, "Network error")
            return
        }

        completion(success, message)
    }.resume()
}

// Usage
updateProfile(
    fullName: "John Doe",
    username: "johndoe123",
    bio: "Hair stylist",
    profession: "Professional Hair Stylist",
    interests: ["Hair Styling", "Makeup"],
    profileImage: selectedImage,
    token: authToken
) { success, message in
    if success {
        print("Profile updated: \(message)")
    } else {
        print("Error: \(message)")
    }
}
```

---

### Android - Kotlin - Update Profile

```kotlin
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.asRequestBody
import java.io.File

fun updateProfile(
    fullName: String? = null,
    username: String? = null,
    bio: String? = null,
    profession: String? = null,
    interests: List<String>? = null,
    profileImageFile: File? = null,
    token: String,
    callback: (Boolean, String) -> Unit
) {
    val client = OkHttpClient()

    val requestBody = MultipartBody.Builder()
        .setType(MultipartBody.FORM)
        .apply {
            fullName?.let { addFormDataPart("full_name", it) }
            username?.let { addFormDataPart("username", it) }
            bio?.let { addFormDataPart("bio", it) }
            profession?.let { addFormDataPart("profession", it) }

            interests?.forEachIndexed { index, interest ->
                addFormDataPart("interests[$index]", interest)
            }

            profileImageFile?.let { file ->
                val mediaType = "image/jpeg".toMediaType()
                addFormDataPart(
                    "profile_picture",
                    file.name,
                    file.asRequestBody(mediaType)
                )
            }
        }
        .build()

    val request = Request.Builder()
        .url("https://your-api.com/api/users/profile")
        .put(requestBody)
        .addHeader("Authorization", "Bearer $token")
        .build()

    client.newCall(request).enqueue(object : Callback {
        override fun onFailure(call: Call, e: IOException) {
            callback(false, "Network error")
        }

        override fun onResponse(call: Call, response: Response) {
            val responseData = response.body?.string()
            val jsonResponse = JSONObject(responseData ?: "{}")
            val success = jsonResponse.optBoolean("success", false)
            val message = jsonResponse.optString("message", "Unknown error")

            callback(success, message)
        }
    })
}

// Usage
updateProfile(
    fullName = "John Doe",
    username = "johndoe123",
    bio = "Hair stylist",
    profession = "Professional Hair Stylist",
    interests = listOf("Hair Styling", "Makeup"),
    profileImageFile = File("/path/to/image.jpg"),
    token = authToken
) { success, message ->
    if (success) {
        println("Profile updated: $message")
    } else {
        println("Error: $message")
    }
}
```

---

## 🧪 Testing with cURL

### Update Profile (Text Only)

```bash
curl -X PUT https://your-api.com/api/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Doe",
    "username": "johndoe123",
    "bio": "Hair stylist and beauty enthusiast",
    "profession": "Professional Hair Stylist",
    "interests": ["Hair Styling", "Makeup", "Fashion"]
  }'
```

### Update Profile with Picture

```bash
curl -X PUT https://your-api.com/api/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "full_name=John Doe" \
  -F "username=johndoe123" \
  -F "bio=Hair stylist and beauty enthusiast" \
  -F "profession=Professional Hair Stylist" \
  -F "interests[]=Hair Styling" \
  -F "interests[]=Makeup" \
  -F "interests[]=Fashion" \
  -F "profile_picture=@/path/to/profile.jpg"
```

### Update Only Profile Picture

```bash
curl -X PUT https://your-api.com/api/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "profile_picture=@/path/to/new-profile.jpg"
```

---

## 📸 Profile Picture Requirements

**Accepted Formats:**

-   JPEG (.jpg, .jpeg)
-   PNG (.png)
-   GIF (.gif)

**Size Limits:**

-   Maximum file size: **2 MB (2048 KB)**
-   Recommended dimensions: **500x500px to 1000x1000px**
-   Aspect ratio: **Square (1:1)** recommended

**Storage:**

-   Stored on AWS S3
-   Served via CDN for fast loading
-   Old profile pictures are automatically deleted when uploading new ones

---

## 🎨 Best Practices

### Image Upload

1. **Compress images** before upload to reduce file size
2. **Crop to square** for consistent display
3. **Validate file size** on client side before upload
4. **Show loading indicator** during upload
5. **Handle errors gracefully** with user-friendly messages

### Profile Updates

1. **Validate username uniqueness** before submission
2. **Limit bio length** (max 500 characters)
3. **Show character counter** for bio field
4. **Provide interest suggestions** from available list
5. **Save draft locally** to prevent data loss

### UX Guidelines

1. **Show preview** of selected image before upload
2. **Allow image cropping/editing** before upload
3. **Provide feedback** during upload process
4. **Update UI immediately** after successful update
5. **Cache profile data** for offline viewing

---

## 🔒 Security Considerations

1. **Authentication Required** - All profile endpoints require valid auth token
2. **File Validation** - Server validates file type and size
3. **Username Uniqueness** - System prevents duplicate usernames
4. **Rate Limiting** - Profile updates may be rate-limited
5. **Malicious Files** - Server scans uploaded files

---

## ❌ Common Errors

### "The username has already been taken"

-   **Cause:** Username is already in use by another user
-   **Solution:** Choose a different username

### "The profile picture must be an image"

-   **Cause:** Uploaded file is not a valid image format
-   **Solution:** Upload JPEG, PNG, or GIF only

### "The profile picture may not be greater than 2048 kilobytes"

-   **Cause:** Image file size exceeds 2MB
-   **Solution:** Compress or resize image before upload

### "Unauthenticated"

-   **Cause:** Missing or invalid auth token
-   **Solution:** Ensure user is logged in and token is valid

---

## 📝 Related Endpoints

### Get Current User Profile

```bash
GET /api/me
Authorization: Bearer YOUR_TOKEN
```

### Get Another User's Profile

```bash
GET /api/users/{user_id}
Authorization: Bearer YOUR_TOKEN
```

### Search Users

```bash
GET /api/users?q=search_term
Authorization: Bearer YOUR_TOKEN
```

### Get Available Interests

```bash
GET /api/interests
Authorization: Bearer YOUR_TOKEN
```

---

## 🧩 Complete Profile Update Flow

1. **Fetch current profile** (`GET /api/me`)
2. **Display edit form** with current values
3. **User makes changes** (text fields and/or image)
4. **Validate input** on client side
5. **Upload changes** (`PUT /api/users/profile`)
6. **Show success message**
7. **Update local state/cache** with new profile data
8. **Navigate back** to profile view

---

## 📊 Response Data Structure

```typescript
interface User {
    id: number;
    name: string;
    full_name: string;
    username: string;
    email: string;
    bio: string | null;
    profession: string | null;
    profile_picture: string | null;
    interests: string[];
    is_business: boolean;
    followers_count: number;
    following_count: number;
    posts_count: number;
    created_at: string;
    updated_at: string;
    email_verified_at: string | null;
}
```

---

## 🚀 Production Checklist

Before going live:

-   [ ] Test profile picture upload with various file sizes
-   [ ] Test with all supported image formats (JPEG, PNG, GIF)
-   [ ] Verify old profile pictures are deleted from S3
-   [ ] Test username uniqueness validation
-   [ ] Implement proper error handling
-   [ ] Add loading states for uploads
-   [ ] Test image compression on mobile
-   [ ] Verify CDN URLs are working
-   [ ] Test offline handling
-   [ ] Implement retry logic for failed uploads

---

**Last Updated:** October 2025  
**API Version:** 1.0

For more documentation, see:

-   [User API Documentation](USER_API_DOCUMENTATION.md)
-   [Upload Documentation](UPLOAD_DOCUMENTATION.md)
-   [API Documentation](API_DOCUMENTATION.md)
