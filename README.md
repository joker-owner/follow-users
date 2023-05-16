# Auto-follow-unfollow
Follow and unfollow users automatically

## Running the script

- Copy following list from srcUser to destUser
```javascript
php script.php -c <srcToken> <destToken>
```
- Adjust following list of srcUser
**Among the following list of srcUser, if a user doesn't follow the srcUser, then remove him from following list.**
```javascript
php script.php -a <srcToken>
```
- Delete all following list of srcUser
```javascript
php script.php -d <srcToken>
```

 **srcToken**:   personal access token of **src** user

 **destToken**:  personal access token of **target** user