# artflow.ai portraits

This is a collection of 45,458 portraits scraped from artflow.ai.

The images are licensed by the copyright holder
([artflow.ai](https://artflow.ai/)) under the [Creative Commons
Attribution](https://creativecommons.org/licenses/by/4.0/) license, but are not
made available by the copyright holder in a format that can be easily consumed
in bulk.

The images in this repository are automatically collected, converted from WEBP
to JPEG, and saved according to the following format:

    [text-prompt]-[artflow-id].jpg

The text prompt is normalized by replacing all sequences of one or more
non-alphanumeric ASCII characters with hyphens. In order to keep directory sizes
manageable, the resulting files are then chunked according to the last two
digits of their numeric IDs.

From the [project FAQ](https://artflow.ai/about/):

> **Can I use the images from Artflow in my project?**
>
> Yes, as long as you attribute Artflow. All images are licensed under CC BY.
> You can find more information in our Terms of Service.

**Note**: The creator and copyright holder of these works is artflow.ai, and
must be credited accordingly. Your use of the works is subject to the [terms of
service](https://artflow.ai/about/#terms).
